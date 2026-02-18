<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostMeta;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MediaService
{
    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    /**
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

    public function updateAttachment(Post $media, array $data): Post
    {
        $updates = [
            'post_modified' => now(),
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

        switch ($action) {
            case 'crop':
                $image = $this->applyCrop($image, $params);
                break;
            case 'rotate':
                $image = $this->applyRotate($image, $params);
                break;
            case 'flip':
                $image = $this->applyFlip($image, $params);
                break;
            case 'scale':
                $image = $this->applyScale($image, $params);
                break;
            default:
                throw new InvalidArgumentException('Unsupported edit action.');
        }

        $this->saveImage($image, $absolutePath, $mimeType);
        imagedestroy($image);

        $metadata = $this->buildAttachmentMetadata($relativePath, $mimeType);
        $this->cleanupGeneratedSizes($relativePath, $metaMap['_wp_attachment_metadata'] ?? null);
        if (str_starts_with($mimeType, 'image/')) {
            $metadata['sizes'] = $this->generateImageSizes($relativePath, $mimeType);
        }

        PostMeta::updateOrCreate(
            ['post_id' => $media->id, 'meta_key' => '_wp_attachment_metadata'],
            ['meta_value' => json_encode($metadata)],
        );

        $media->update([
            'post_modified' => now(),
            'post_modified_gmt' => now('UTC'),
        ]);

        return $media->fresh(['meta', 'author', 'parent']);
    }

    public function deleteAttachment(Post $media): void
    {
        $metaMap = $this->getMetaMap($media);
        $relativePath = $metaMap['_wp_attached_file'] ?? null;
        $metadataJson = $metaMap['_wp_attachment_metadata'] ?? null;

        if ($relativePath) {
            Storage::disk('public')->delete($relativePath);
        }

        $this->cleanupGeneratedSizes($relativePath, $metadataJson);

        PostMeta::where('post_id', $media->id)->delete();
        $media->delete();
    }

    private function uploadSingle(UploadedFile $file, User $user, ?int $attachedTo = null): Post
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = Str::slug($baseName);
        if ($safeBase === '') {
            $safeBase = 'file-'.Str::lower(Str::ulid());
        }

        $directory = $this->uploadDirectory();
        $fileName = $this->uniqueFileName($directory, $safeBase, $extension);
        $relativePath = trim($directory.'/'.$fileName, '/');

        Storage::disk('public')->putFileAs($directory, $file, $fileName);

        $absolutePath = Storage::disk('public')->path($relativePath);
        $mimeType = $file->getMimeType() ?: ($this->detectMimeType($absolutePath) ?? 'application/octet-stream');
        $title = trim($baseName) !== '' ? $baseName : $fileName;
        $now = now();

        $post = Post::create([
            'author_id' => $user->id,
            'post_date' => $now,
            'post_date_gmt' => now('UTC'),
            'content' => '',
            'title' => $title,
            'excerpt' => '',
            'status' => 'inherit',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'password' => '',
            'slug' => $this->uniqueAttachmentSlug($safeBase),
            'post_modified' => $now,
            'post_modified_gmt' => now('UTC'),
            'content_filtered' => '',
            'parent_id' => $attachedTo ?? 0,
            'guid' => Storage::disk('public')->url($relativePath),
            'menu_order' => 0,
            'type' => 'attachment',
            'mime_type' => $mimeType,
            'comment_count' => 0,
        ]);

        $metadata = $this->buildAttachmentMetadata($relativePath, $mimeType);
        if (str_starts_with($mimeType, 'image/')) {
            $metadata['sizes'] = $this->generateImageSizes($relativePath, $mimeType);
        }

        PostMeta::create([
            'post_id' => $post->id,
            'meta_key' => '_wp_attached_file',
            'meta_value' => $relativePath,
        ]);

        PostMeta::create([
            'post_id' => $post->id,
            'meta_key' => '_wp_attachment_metadata',
            'meta_value' => json_encode($metadata),
        ]);

        PostMeta::create([
            'post_id' => $post->id,
            'meta_key' => '_wp_attachment_image_alt',
            'meta_value' => '',
        ]);

        return $post->fresh(['meta', 'author', 'parent']);
    }

    private function buildAttachmentMetadata(string $relativePath, string $mimeType): array
    {
        $absolutePath = Storage::disk('public')->path($relativePath);
        $metadata = [
            'file' => $relativePath,
            'filesize' => is_file($absolutePath) ? (filesize($absolutePath) ?: 0) : 0,
            'sizes' => [],
        ];

        if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
            $dimensions = @getimagesize($absolutePath);
            if ($dimensions !== false) {
                $metadata['width'] = $dimensions[0];
                $metadata['height'] = $dimensions[1];
            }
        }

        return $metadata;
    }

    /**
     * @return array<string, array<string, int|string>>
     */
    private function generateImageSizes(string $relativePath, string $mimeType): array
    {
        $disk = Storage::disk('public');
        $absolutePath = $disk->path($relativePath);
        $dimensions = @getimagesize($absolutePath);

        if ($dimensions === false) {
            return [];
        }

        [$srcW, $srcH] = [$dimensions[0], $dimensions[1]];
        $pathInfo = pathinfo($relativePath);
        $dir = trim($pathInfo['dirname'] ?? '', '.');
        $base = $pathInfo['filename'];
        $ext = strtolower($pathInfo['extension'] ?? 'jpg');

        $targets = [
            'thumbnail' => [
                'w' => (int) ($this->optionService->get('thumbnail_size_w', 150) ?: 150),
                'h' => (int) ($this->optionService->get('thumbnail_size_h', 150) ?: 150),
                'crop' => $this->isTruthy($this->optionService->get('thumbnail_crop', '1')),
            ],
            'medium' => [
                'w' => (int) ($this->optionService->get('medium_size_w', 300) ?: 300),
                'h' => (int) ($this->optionService->get('medium_size_h', 300) ?: 300),
                'crop' => false,
            ],
            'large' => [
                'w' => (int) ($this->optionService->get('large_size_w', 1024) ?: 1024),
                'h' => (int) ($this->optionService->get('large_size_h', 1024) ?: 1024),
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

            $newName = "{$base}-{$target['w']}x{$target['h']}.{$ext}";
            $newRelativePath = trim(($dir !== '' ? $dir.'/' : '').$newName, '/');
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
                'file' => $newName,
                'width' => $processed['width'],
                'height' => $processed['height'],
                'mime-type' => $mimeType,
            ];
        }

        return $sizes;
    }

    /**
     * @return array{width:int,height:int}|null
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
        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($crop) {
            $srcRatio = $srcW / max($srcH, 1);
            $targetRatio = $targetW / max($targetH, 1);

            if ($srcRatio > $targetRatio) {
                $cropH = $srcH;
                $cropW = (int) round($srcH * $targetRatio);
                $srcX = (int) round(($srcW - $cropW) / 2);
                $srcY = 0;
            } else {
                $cropW = $srcW;
                $cropH = (int) round($srcW / max($targetRatio, 0.00001));
                $srcX = 0;
                $srcY = (int) round(($srcH - $cropH) / 2);
            }

            $destW = $targetW;
            $destH = $targetH;
            $dest = $this->blankCanvas($destW, $destH, $mimeType);
            imagecopyresampled($dest, $source, 0, 0, $srcX, $srcY, $destW, $destH, $cropW, $cropH);
        } else {
            $ratio = min($targetW / max($srcW, 1), $targetH / max($srcH, 1), 1);
            $destW = max((int) round($srcW * $ratio), 1);
            $destH = max((int) round($srcH * $ratio), 1);
            $dest = $this->blankCanvas($destW, $destH, $mimeType);
            imagecopyresampled($dest, $source, 0, 0, 0, 0, $destW, $destH, $srcW, $srcH);
        }

        $this->ensureDirectory(dirname($targetPath));
        $this->saveImage($dest, $targetPath, $mimeType);

        imagedestroy($source);
        imagedestroy($dest);

        return ['width' => $destW, 'height' => $destH];
    }

    private function uploadDirectory(): string
    {
        $yearMonth = $this->isTruthy($this->optionService->get('uploads_use_yearmonth_folders', '1'));
        if (!$yearMonth) {
            return 'uploads';
        }

        return 'uploads/'.now()->format('Y').'/'.now()->format('m');
    }

    private function uniqueFileName(string $directory, string $baseName, string $extension): string
    {
        $disk = Storage::disk('public');
        $counter = 0;

        do {
            $suffix = $counter > 0 ? '-'.$counter : '';
            $candidate = $baseName.$suffix.'.'.$extension;
            $counter++;
        } while ($disk->exists(trim($directory.'/'.$candidate, '/')));

        return $candidate;
    }

    private function uniqueAttachmentSlug(string $base): string
    {
        $slug = $base;
        $counter = 2;

        while (Post::where('type', 'attachment')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    private function getMetaMap(Post $media): array
    {
        $rows = PostMeta::where('post_id', $media->id)->get(['meta_key', 'meta_value']);
        $map = [];
        foreach ($rows as $row) {
            $map[$row->meta_key] = $row->meta_value;
        }

        return $map;
    }

    private function cleanupGeneratedSizes(?string $relativePath, ?string $metadataJson): void
    {
        if (!$relativePath || !$metadataJson) {
            return;
        }

        $decoded = json_decode($metadataJson, true);
        if (!is_array($decoded) || !isset($decoded['sizes']) || !is_array($decoded['sizes'])) {
            return;
        }

        $dir = pathinfo($relativePath, PATHINFO_DIRNAME);
        if ($dir === '.' || $dir === '') {
            $dir = '';
        }

        foreach ($decoded['sizes'] as $size) {
            if (!is_array($size) || empty($size['file'])) {
                continue;
            }

            $sizePath = trim(($dir !== '' ? $dir.'/' : '').$size['file'], '/');
            Storage::disk('public')->delete($sizePath);
        }
    }

    private function detectMimeType(string $absolutePath): ?string
    {
        $mime = @mime_content_type($absolutePath);
        if ($mime === false) {
            return null;
        }

        return $mime;
    }

    private function loadImage(string $absolutePath, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($absolutePath),
            'image/png' => imagecreatefrompng($absolutePath),
            'image/gif' => imagecreatefromgif($absolutePath),
            'image/webp' => imagecreatefromwebp($absolutePath),
            default => throw new InvalidArgumentException("Unsupported image mime type: {$mimeType}"),
        };
    }

    private function saveImage($image, string $absolutePath, string $mimeType): void
    {
        $this->ensureDirectory(dirname($absolutePath));

        match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, $absolutePath, 90),
            'image/png' => imagepng($image, $absolutePath, 6),
            'image/gif' => imagegif($image, $absolutePath),
            'image/webp' => imagewebp($image, $absolutePath, 85),
            default => throw new InvalidArgumentException("Unsupported image mime type: {$mimeType}"),
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

    private function applyCrop($image, array $params)
    {
        $x = max((int) ($params['x'] ?? 0), 0);
        $y = max((int) ($params['y'] ?? 0), 0);
        $width = max((int) ($params['width'] ?? 0), 1);
        $height = max((int) ($params['height'] ?? 0), 1);

        $srcW = imagesx($image);
        $srcH = imagesy($image);

        $width = min($width, $srcW - $x);
        $height = min($height, $srcH - $y);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefilledrectangle($dest, 0, 0, $width, $height, $transparent);
        imagecopyresampled($dest, $image, 0, 0, $x, $y, $width, $height, $width, $height);

        imagedestroy($image);

        return $dest;
    }

    private function applyRotate($image, array $params)
    {
        $angle = (float) ($params['angle'] ?? 0);
        $rotated = imagerotate($image, -$angle, 0);

        imagedestroy($image);

        return $rotated;
    }

    private function applyFlip($image, array $params)
    {
        $mode = strtolower((string) ($params['mode'] ?? 'horizontal'));
        $flipConstant = $mode === 'vertical' ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL;

        if (function_exists('imageflip')) {
            imageflip($image, $flipConstant);
            return $image;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $flipped = imagecreatetruecolor($w, $h);
        imagealphablending($flipped, false);
        imagesavealpha($flipped, true);
        $transparent = imagecolorallocatealpha($flipped, 0, 0, 0, 127);
        imagefilledrectangle($flipped, 0, 0, $w, $h, $transparent);

        if ($flipConstant === IMG_FLIP_HORIZONTAL) {
            for ($x = 0; $x < $w; $x++) {
                imagecopy($flipped, $image, $w - $x - 1, 0, $x, 0, 1, $h);
            }
        } else {
            for ($y = 0; $y < $h; $y++) {
                imagecopy($flipped, $image, 0, $h - $y - 1, 0, $y, $w, 1);
            }
        }

        imagedestroy($image);

        return $flipped;
    }

    private function applyScale($image, array $params)
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);

        $targetW = isset($params['width']) ? (int) $params['width'] : null;
        $targetH = isset($params['height']) ? (int) $params['height'] : null;

        if (!$targetW && !$targetH) {
            throw new InvalidArgumentException('Scale action requires width and/or height.');
        }

        if ($targetW && !$targetH) {
            $ratio = $targetW / max($srcW, 1);
            $targetH = (int) round($srcH * $ratio);
        } elseif (!$targetW && $targetH) {
            $ratio = $targetH / max($srcH, 1);
            $targetW = (int) round($srcW * $ratio);
        }

        $targetW = max((int) $targetW, 1);
        $targetH = max((int) $targetH, 1);

        $scaled = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        $transparent = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefilledrectangle($scaled, 0, 0, $targetW, $targetH, $transparent);
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);

        imagedestroy($image);

        return $scaled;
    }

    private function ensureDirectory(string $absoluteDirectory): void
    {
        if (!is_dir($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0775, true);
        }
    }

    private function isTruthy(mixed $value): bool
    {
        $string = strtolower(trim((string) $value));

        return in_array($string, ['1', 'true', 'yes', 'on'], true);
    }
}
