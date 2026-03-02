<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostMeta;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Handles file upload, thumbnail generation, metadata management, and image
 * editing for the Media Library.
 *
 * Media items are stored as `posts` with type='attachment'. File metadata is
 * kept in `post_meta` (_wp_attached_file, _wp_attachment_metadata, etc.).
 */
class MediaService
{
    /**
     * Allowed MIME type → extension map.
     * Single source of truth for upload validation — see UploadMediaRequest.
     */
    private const ALLOWED_TYPES = [
        'image/jpeg'                                                                 => 'jpg',
        'image/png'                                                                  => 'png',
        'image/gif'                                                                  => 'gif',
        'image/webp'                                                                 => 'webp',
        'image/svg+xml'                                                              => 'svg',
        'application/pdf'                                                            => 'pdf',
        'application/msword'                                                         => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/vnd.ms-excel'                                                   => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'application/vnd.ms-powerpoint'                                              => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'video/mp4'                                                                  => 'mp4',
        'audio/mpeg'                                                                 => 'mp3',
        'audio/wav'                                                                  => 'wav',
        'audio/ogg'                                                                  => 'ogg',
        'application/zip'                                                            => 'zip',
    ];

    /**
     * Default image size targets. Actual dimensions are read from options at
     * runtime; these are used as fallbacks only.
     */
    private const IMAGE_SIZE_DEFAULTS = [
        'thumbnail' => ['width' => 150,  'height' => 150,  'crop' => true],
        'medium'    => ['width' => 300,  'height' => 300,  'crop' => false],
        'large'     => ['width' => 1024, 'height' => 1024, 'crop' => false],
    ];

    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    // ═══════════════════════════════════════════════════════════
    //  Public API
    // ═══════════════════════════════════════════════════════════

    /**
     * Upload one or more files and create the corresponding attachment posts.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, Post>
     */
    public function upload(array $files, User $user, ?int $attachedTo = null): array
    {
        $uploaded = [];

        foreach ($files as $file) {
            $uploaded[] = $this->uploadSingle($file, $user, $attachedTo);
        }

        return $uploaded;
    }

    /**
     * Update the editable metadata fields of an attachment (title, caption,
     * alt text, description).
     */
    public function updateAttachment(Post $media, array $data): Post
    {
        $updates = [
            'post_modified'     => now(),
            'post_modified_gmt' => now('UTC'),
        ];

        if (array_key_exists('title', $data)) {
            $updates['title'] = $data['title'] ?? '';
        }
        if (array_key_exists('caption', $data)) {
            $updates['excerpt'] = $data['caption'] ?? '';
        }
        if (array_key_exists('description', $data)) {
            $updates['content'] = $data['description'] ?? '';
        }

        $media->update($updates);

        if (array_key_exists('alt_text', $data)) {
            PostMeta::updateOrCreate(
                ['post_id' => $media->id, 'meta_key' => '_wp_attachment_image_alt'],
                ['meta_value' => $data['alt_text'] ?? ''],
            );
        }

        return $media->fresh(['meta', 'author', 'parent']);
    }

    /**
     * Apply a destructive image edit (crop / rotate / flip / scale) in-place,
     * then regenerate thumbnails.
     *
     * @throws InvalidArgumentException
     */
    public function editAttachment(Post $media, string $action, array $params): Post
    {
        $metaMap = $this->getMetaMap($media);
        $relativePath = $metaMap['_wp_attached_file'] ?? null;

        if (!$relativePath) {
            throw new InvalidArgumentException('Attachment file path is missing.');
        }

        $absolutePath = Storage::disk('public')->path($relativePath);
        if (!is_file($absolutePath)) {
            throw new InvalidArgumentException('Attachment file does not exist on disk.');
        }

        $mimeType = $media->mime_type ?: ($this->detectMimeType($absolutePath) ?? '');
        if (!str_starts_with($mimeType, 'image/') || $mimeType === 'image/svg+xml') {
            throw new InvalidArgumentException('Only raster images can be edited.');
        }

        $image = $this->loadImage($absolutePath, $mimeType);
        $image = match ($action) {
            'crop'   => $this->applyCrop($image, $params),
            'rotate' => $this->applyRotate($image, $params),
            'flip'   => $this->applyFlip($image, $params),
            'scale'  => $this->applyScale($image, $params),
            default  => throw new InvalidArgumentException('Unsupported edit action.'),
        };

        $this->saveImage($image, $absolutePath, $mimeType);
        imagedestroy($image);

        $metadata = $this->buildAttachmentMetadata($relativePath, $mimeType);
        $this->cleanupGeneratedSizes($relativePath, $metaMap['_wp_attachment_metadata'] ?? null);
        $metadata['sizes'] = $this->generateImageSizes($relativePath, $mimeType);

        PostMeta::updateOrCreate(
            ['post_id' => $media->id, 'meta_key' => '_wp_attachment_metadata'],
            ['meta_value' => json_encode($metadata)],
        );

        $media->update([
            'post_modified'     => now(),
            'post_modified_gmt' => now('UTC'),
        ]);

        return $media->fresh(['meta', 'author', 'parent']);
    }

    /**
     * Permanently delete an attachment: file + generated sizes + meta + post.
     */
    public function deleteAttachment(Post $media): void
    {
        $metaMap      = $this->getMetaMap($media);
        $relativePath = $metaMap['_wp_attached_file'] ?? null;
        $metadataJson = $metaMap['_wp_attachment_metadata'] ?? null;

        if ($relativePath) {
            Storage::disk('public')->delete($relativePath);
        }

        $this->cleanupGeneratedSizes($relativePath, $metadataJson);

        PostMeta::where('post_id', $media->id)->delete();
        $media->delete();
    }

    /**
     * Return the public URL for an attachment.
     */
    public function getUrl(Post $attachment): ?string
    {
        $filePath = $attachment->meta()
            ->where('meta_key', '_wp_attached_file')
            ->value('meta_value');

        return $filePath ? Storage::disk('public')->url($filePath) : null;
    }

    /**
     * Parse the stored JSON metadata for an attachment.
     *
     * @return array{width?: int, height?: int, filesize?: int, file?: string, sizes?: array}
     */
    public function getMetadata(Post $attachment): array
    {
        $raw = $attachment->meta()
            ->where('meta_key', '_wp_attachment_metadata')
            ->value('meta_value');

        return $raw ? (json_decode($raw, true) ?: []) : [];
    }

    /**
     * Return the list of allowed file extensions for upload validation.
     *
     * @return string[]  e.g. ['jpg', 'png', 'gif', …]
     */
    public static function allowedExtensions(): array
    {
        return array_values(self::ALLOWED_TYPES);
    }

    // ═══════════════════════════════════════════════════════════
    //  Upload Internals
    // ═══════════════════════════════════════════════════════════

    private function uploadSingle(UploadedFile $file, User $user, ?int $attachedTo = null): Post
    {
        $sanitised  = $this->sanitiseFilename($file->getClientOriginalName());
        $extension  = strtolower(pathinfo($sanitised, PATHINFO_EXTENSION));
        $safeBase   = pathinfo($sanitised, PATHINFO_FILENAME);

        $directory    = $this->uploadDirectory();
        $fileName     = $this->uniqueFileName($directory, $safeBase, $extension);
        $relativePath = trim($directory . '/' . $fileName, '/');

        Storage::disk('public')->putFileAs($directory, $file, $fileName);

        $absolutePath = Storage::disk('public')->path($relativePath);
        $mimeType     = $file->getMimeType() ?: ($this->detectMimeType($absolutePath) ?? 'application/octet-stream');
        $title        = trim(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $fileName;
        $now          = now();

        $post = Post::create([
            'author_id'         => $user->id,
            'post_date'         => $now,
            'post_date_gmt'     => now('UTC'),
            'content'           => '',
            'title'             => $title,
            'excerpt'           => '',
            'status'            => 'inherit',
            'comment_status'    => 'closed',
            'ping_status'       => 'closed',
            'password'          => '',
            'slug'              => $this->uniqueAttachmentSlug($safeBase),
            'post_modified'     => $now,
            'post_modified_gmt' => now('UTC'),
            'content_filtered'  => '',
            'parent_id'         => $attachedTo ?? 0,
            'guid'              => Storage::disk('public')->url($relativePath),
            'menu_order'        => 0,
            'type'              => 'attachment',
            'mime_type'         => $mimeType,
            'comment_count'     => 0,
        ]);

        $metadata = $this->buildAttachmentMetadata($relativePath, $mimeType);
        if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
            $metadata['sizes'] = $this->generateImageSizes($relativePath, $mimeType);
        }

        PostMeta::insert([
            ['post_id' => $post->id, 'meta_key' => '_wp_attached_file',        'meta_value' => $relativePath],
            ['post_id' => $post->id, 'meta_key' => '_wp_attachment_metadata',  'meta_value' => json_encode($metadata)],
            ['post_id' => $post->id, 'meta_key' => '_wp_attachment_image_alt', 'meta_value' => ''],
        ]);

        return $post->fresh(['meta', 'author', 'parent']);
    }

    private function buildAttachmentMetadata(string $relativePath, string $mimeType): array
    {
        $absolutePath = Storage::disk('public')->path($relativePath);
        $metadata = [
            'file'     => $relativePath,
            'filesize' => is_file($absolutePath) ? (filesize($absolutePath) ?: 0) : 0,
            'sizes'    => [],
        ];

        if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
            $dimensions = @getimagesize($absolutePath);
            if ($dimensions !== false) {
                $metadata['width']  = $dimensions[0];
                $metadata['height'] = $dimensions[1];
            }
        }

        return $metadata;
    }

    // ═══════════════════════════════════════════════════════════
    //  Image Size Generation
    // ═══════════════════════════════════════════════════════════

    /**
     * @return array<string, array<string, int|string>>
     */
    private function generateImageSizes(string $relativePath, string $mimeType): array
    {
        if ($mimeType === 'image/svg+xml') {
            return [];
        }

        $disk         = Storage::disk('public');
        $absolutePath = $disk->path($relativePath);
        $dimensions   = @getimagesize($absolutePath);

        if ($dimensions === false) {
            return [];
        }

        [$srcW, $srcH] = [$dimensions[0], $dimensions[1]];
        $pathInfo = pathinfo($relativePath);
        $dir      = trim($pathInfo['dirname'] ?? '', '.');
        $base     = $pathInfo['filename'];
        $ext      = strtolower($pathInfo['extension'] ?? 'jpg');

        $targets = [
            'thumbnail' => [
                'w'    => (int) ($this->optionService->get('thumbnail_size_w', self::IMAGE_SIZE_DEFAULTS['thumbnail']['width']) ?: self::IMAGE_SIZE_DEFAULTS['thumbnail']['width']),
                'h'    => (int) ($this->optionService->get('thumbnail_size_h', self::IMAGE_SIZE_DEFAULTS['thumbnail']['height']) ?: self::IMAGE_SIZE_DEFAULTS['thumbnail']['height']),
                'crop' => $this->isTruthy($this->optionService->get('thumbnail_crop', '1')),
            ],
            'medium' => [
                'w'    => (int) ($this->optionService->get('medium_size_w', self::IMAGE_SIZE_DEFAULTS['medium']['width']) ?: self::IMAGE_SIZE_DEFAULTS['medium']['width']),
                'h'    => (int) ($this->optionService->get('medium_size_h', self::IMAGE_SIZE_DEFAULTS['medium']['height']) ?: self::IMAGE_SIZE_DEFAULTS['medium']['height']),
                'crop' => false,
            ],
            'large' => [
                'w'    => (int) ($this->optionService->get('large_size_w', self::IMAGE_SIZE_DEFAULTS['large']['width']) ?: self::IMAGE_SIZE_DEFAULTS['large']['width']),
                'h'    => (int) ($this->optionService->get('large_size_h', self::IMAGE_SIZE_DEFAULTS['large']['height']) ?: self::IMAGE_SIZE_DEFAULTS['large']['height']),
                'crop' => false,
            ],
        ];

        $sizes = [];

        foreach ($targets as $sizeKey => $target) {
            if ($target['w'] <= 0 || $target['h'] <= 0) {
                continue;
            }

            if (!$target['crop'] && $srcW <= $target['w'] && $srcH <= $target['h']) {
                continue;
            }

            $newName         = "{$base}-{$target['w']}x{$target['h']}.{$ext}";
            $newRelativePath = trim(($dir !== '' ? $dir . '/' : '') . $newName, '/');
            $newAbsolutePath = $disk->path($newRelativePath);

            $processed = $this->createResizedImage(
                $absolutePath,
                $newAbsolutePath,
                $mimeType,
                $target['w'],
                $target['h'],
                $target['crop'],
            );

            if ($processed === null) {
                continue;
            }

            $sizes[$sizeKey] = [
                'file'      => $newName,
                'width'     => $processed['width'],
                'height'    => $processed['height'],
                'mime-type' => $mimeType,
            ];
        }

        return $sizes;
    }

    /**
     * @return array{width: int, height: int}|null
     */
    private function createResizedImage(
        string $sourcePath,
        string $targetPath,
        string $mimeType,
        int $targetW,
        int $targetH,
        bool $crop
    ): ?array {
        $source = $this->loadImage($sourcePath, $mimeType);
        $srcW   = imagesx($source);
        $srcH   = imagesy($source);

        if ($crop) {
            $srcRatio    = $srcW / max($srcH, 1);
            $targetRatio = $targetW / max($targetH, 1);

            if ($srcRatio > $targetRatio) {
                $cropH = $srcH;
                $cropW = (int) round($srcH * $targetRatio);
                $srcX  = (int) round(($srcW - $cropW) / 2);
                $srcY  = 0;
            } else {
                $cropW = $srcW;
                $cropH = (int) round($srcW / max($targetRatio, 0.00001));
                $srcX  = 0;
                $srcY  = (int) round(($srcH - $cropH) / 2);
            }

            $destW = $targetW;
            $destH = $targetH;
            $dest  = $this->blankCanvas($destW, $destH, $mimeType);
            imagecopyresampled($dest, $source, 0, 0, $srcX, $srcY, $destW, $destH, $cropW, $cropH);
        } else {
            $ratio = min($targetW / max($srcW, 1), $targetH / max($srcH, 1), 1);
            $destW = max((int) round($srcW * $ratio), 1);
            $destH = max((int) round($srcH * $ratio), 1);
            $dest  = $this->blankCanvas($destW, $destH, $mimeType);
            imagecopyresampled($dest, $source, 0, 0, 0, 0, $destW, $destH, $srcW, $srcH);
        }

        $this->ensureDirectory(dirname($targetPath));
        $this->saveImage($dest, $targetPath, $mimeType);

        imagedestroy($source);
        imagedestroy($dest);

        return ['width' => $destW, 'height' => $destH];
    }

    // ═══════════════════════════════════════════════════════════
    //  Image Edit Operations
    // ═══════════════════════════════════════════════════════════

    private function applyCrop($image, array $params)
    {
        $x      = max((int) ($params['x'] ?? 0), 0);
        $y      = max((int) ($params['y'] ?? 0), 0);
        $width  = max((int) ($params['width'] ?? 0), 1);
        $height = max((int) ($params['height'] ?? 0), 1);

        $srcW   = imagesx($image);
        $srcH   = imagesy($image);
        $width  = min($width, $srcW - $x);
        $height = min($height, $srcH - $y);

        $dest = $this->blankCanvas($width, $height, 'image/png');
        imagecopyresampled($dest, $image, 0, 0, $x, $y, $width, $height, $width, $height);
        imagedestroy($image);

        return $dest;
    }

    private function applyRotate($image, array $params)
    {
        $angle   = (float) ($params['angle'] ?? 0);
        $rotated = imagerotate($image, -$angle, 0);
        imagedestroy($image);

        return $rotated;
    }

    private function applyFlip($image, array $params)
    {
        $mode         = strtolower((string) ($params['mode'] ?? 'horizontal'));
        $flipConstant = $mode === 'vertical' ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL;

        imageflip($image, $flipConstant);

        return $image;
    }

    private function applyScale($image, array $params)
    {
        $srcW    = imagesx($image);
        $srcH    = imagesy($image);
        $targetW = isset($params['width'])  ? (int) $params['width']  : null;
        $targetH = isset($params['height']) ? (int) $params['height'] : null;

        if (!$targetW && !$targetH) {
            throw new InvalidArgumentException('Scale action requires width and/or height.');
        }

        if ($targetW && !$targetH) {
            $targetH = (int) round($srcH * ($targetW / max($srcW, 1)));
        } elseif (!$targetW && $targetH) {
            $targetW = (int) round($srcW * ($targetH / max($srcH, 1)));
        }

        $targetW = max((int) $targetW, 1);
        $targetH = max((int) $targetH, 1);

        $scaled = $this->blankCanvas($targetW, $targetH, 'image/png');
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);
        imagedestroy($image);

        return $scaled;
    }

    // ═══════════════════════════════════════════════════════════
    //  Low-level GD Helpers
    // ═══════════════════════════════════════════════════════════

    private function loadImage(string $absolutePath, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($absolutePath),
            'image/png'               => imagecreatefrompng($absolutePath),
            'image/gif'               => imagecreatefromgif($absolutePath),
            'image/webp'              => imagecreatefromwebp($absolutePath),
            default => throw new InvalidArgumentException("Unsupported image MIME type: {$mimeType}"),
        };
    }

    private function saveImage($image, string $absolutePath, string $mimeType): void
    {
        $this->ensureDirectory(dirname($absolutePath));

        match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, $absolutePath, 90),
            'image/png'               => imagepng($image, $absolutePath, 6),
            'image/gif'               => imagegif($image, $absolutePath),
            'image/webp'              => imagewebp($image, $absolutePath, 85),
            default => throw new InvalidArgumentException("Unsupported image MIME type: {$mimeType}"),
        };
    }

    private function blankCanvas(int $width, int $height, string $mimeType)
    {
        $canvas = imagecreatetruecolor($width, $height);

        if (in_array($mimeType, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        }

        return $canvas;
    }

    // ═══════════════════════════════════════════════════════════
    //  Filesystem / Naming Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Sanitise a filename: lowercase slug for the name, preserve extension.
     */
    private function sanitiseFilename(string $filename): string
    {
        $name      = pathinfo($filename, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name      = Str::slug($name) ?: 'file';

        return "{$name}.{$extension}";
    }

    private function uploadDirectory(): string
    {
        $useYearMonth = $this->isTruthy($this->optionService->get('uploads_use_yearmonth_folders', '1'));

        return $useYearMonth
            ? 'uploads/' . now()->format('Y') . '/' . now()->format('m')
            : 'uploads';
    }

    private function uniqueFileName(string $directory, string $baseName, string $extension): string
    {
        $disk    = Storage::disk('public');
        $counter = 0;

        do {
            $suffix    = $counter > 0 ? '-' . $counter : '';
            $candidate = $baseName . $suffix . '.' . $extension;
            $counter++;
        } while ($disk->exists(trim($directory . '/' . $candidate, '/')));

        return $candidate;
    }

    private function uniqueAttachmentSlug(string $base): string
    {
        $slug    = $base;
        $counter = 2;

        while (Post::where('type', 'attachment')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }

    private function getMetaMap(Post $media): array
    {
        return PostMeta::where('post_id', $media->id)
            ->get(['meta_key', 'meta_value'])
            ->pluck('meta_value', 'meta_key')
            ->all();
    }

    private function cleanupGeneratedSizes(?string $relativePath, ?string $metadataJson): void
    {
        if (!$relativePath || !$metadataJson) {
            return;
        }

        $decoded = json_decode($metadataJson, true);
        if (!is_array($decoded) || empty($decoded['sizes'])) {
            return;
        }

        $dir = pathinfo($relativePath, PATHINFO_DIRNAME);
        $dir = ($dir === '.' || $dir === '') ? '' : $dir;

        foreach ($decoded['sizes'] as $size) {
            if (!is_array($size) || empty($size['file'])) {
                continue;
            }

            $sizePath = trim(($dir !== '' ? $dir . '/' : '') . $size['file'], '/');
            Storage::disk('public')->delete($sizePath);
        }
    }

    private function detectMimeType(string $absolutePath): ?string
    {
        $mime = @mime_content_type($absolutePath);

        return $mime !== false ? $mime : null;
    }

    private function ensureDirectory(string $absoluteDirectory): void
    {
        if (!is_dir($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0775, true);
        }
    }

    private function isTruthy(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}