<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostMeta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Handles file upload, thumbnail generation, and image editing for the Media Library.
 *
 * Media items are stored as `posts` with type='attachment', and metadata is
 * kept in `post_meta` (e.g. _wp_attached_file, _wp_attachment_metadata).
 */
class MediaService
{
    /**
     * Allowed MIME type → extension map.
     * Acts as the single source of truth for upload validation.
     */
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'video/mp4' => 'mp4',
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/ogg' => 'ogg',
        'application/zip' => 'zip',
    ];

    private const IMAGE_SIZES = [
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'medium' => ['width' => 300, 'height' => 300, 'crop' => false],
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false],
    ];

    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    /**
     * Upload a single file and create the corresponding attachment post.
     *
     * Why store as a Post? WordPress treats media as a post type ('attachment')
     * so all existing Post relationships (meta, taxonomies) work out of the box.
     */
    public function upload(UploadedFile $file, int $authorId): Post
    {
        $sanitisedName = $this->sanitiseFilename($file->getClientOriginalName());
        $relativePath = $this->buildRelativePath($sanitisedName);

        $file->storeAs(
            dirname($relativePath),
            basename($relativePath),
            'public'
        );

        $mimeType = $file->getMimeType();
        $attachmentMetadata = $this->buildMetadata($file, $relativePath);

        $attachment = Post::create([
            'author_id' => $authorId,
            'post_date' => now(),
            'post_date_gmt' => now()->utc(),
            'content' => '',
            'title' => pathinfo($sanitisedName, PATHINFO_FILENAME),
            'excerpt' => '',
            'status' => 'inherit',
            'comment_status' => 'open',
            'ping_status' => 'closed',
            'password' => '',
            'slug' => Str::slug(pathinfo($sanitisedName, PATHINFO_FILENAME)),
            'post_modified' => now(),
            'post_modified_gmt' => now()->utc(),
            'content_filtered' => '',
            'parent_id' => 0,
            'guid' => asset("storage/{$relativePath}"),
            'menu_order' => 0,
            'type' => 'attachment',
            'mime_type' => $mimeType,
            'comment_count' => 0,
        ]);

        $attachment->meta()->createMany([
            ['meta_key' => '_wp_attached_file', 'meta_value' => $relativePath],
            ['meta_key' => '_wp_attachment_metadata', 'meta_value' => json_encode($attachmentMetadata)],
        ]);

        return $attachment;
    }

    /**
     * Permanently delete an attachment: file on disk + post + meta.
     */
    public function deleteAttachment(Post $attachment): void
    {
        $filePath = $attachment->meta()
            ->where('meta_key', '_wp_attached_file')
            ->value('meta_value');

        if ($filePath && \Storage::disk('public')->exists($filePath)) {
            \Storage::disk('public')->delete($filePath);
        }

        $attachment->meta()->delete();
        $attachment->delete();
    }

    /**
     * Build the public URL for a media item.
     */
    public function getUrl(Post $attachment): ?string
    {
        $filePath = $attachment->meta()
            ->where('meta_key', '_wp_attached_file')
            ->value('meta_value');

        if (!$filePath) {
            return null;
        }

        return asset("storage/{$filePath}");
    }

    /**
     * Parse the stored JSON metadata for a media item.
     *
     * @return array{width?: int, height?: int, filesize?: int, file?: string}
     */
    public function getMetadata(Post $attachment): array
    {
        $raw = $attachment->meta()
            ->where('meta_key', '_wp_attachment_metadata')
            ->value('meta_value');

        if (!$raw) {
            return [];
        }

        return json_decode($raw, true) ?: [];
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Sanitise a filename: lowercase, strip unsafe characters, preserve extension.
     */
    private function sanitiseFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $name = Str::slug($name) ?: 'file';

        return "{$name}.{$extension}";
    }

    /**
     * Build year/month subfolder path if the option is enabled.
     */
    private function buildRelativePath(string $filename): string
    {
        $useYearMonth = $this->optionService->get('uploads_use_yearmonth_folders', '1');

        if ($useYearMonth === '1') {
            $subDir = now()->format('Y/m');
            return "uploads/{$subDir}/{$filename}";
        }

        return "uploads/{$filename}";
    }

    /**
     * Build attachment metadata array (dimensions for images, filesize for all).
     */
    private function buildMetadata(UploadedFile $file, string $relativePath): array
    {
        $metadata = [
            'file' => $relativePath,
            'filesize' => $file->getSize(),
        ];

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $dimensions = @getimagesize($file->getPathname());

            if ($dimensions) {
                $metadata['width'] = $dimensions[0];
                $metadata['height'] = $dimensions[1];
            }

            $metadata['sizes'] = self::IMAGE_SIZES;
        }

        return $metadata;
    }

    /**
     * Get the list of allowed file extensions for upload validation.
     *
     * @return string[] e.g. ['jpg', 'png', 'gif', ...]
     */
    public static function allowedExtensions(): array
    {
        return array_values(self::ALLOWED_TYPES);
    }
}
