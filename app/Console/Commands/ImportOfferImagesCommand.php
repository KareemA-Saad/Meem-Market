<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\Post;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class ImportOfferImagesCommand extends Command
{
    private const SUPPORTED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    protected $signature = 'offers:import-images
        {--source=public/images/offers : Source directory that contains offer images}
        {--category-slug=coming-winter-offers : Target offer category slug}
        {--name-prefix=offer : Numbered image/title prefix (offer-1, offer-2, ...)}
        {--start-order=1 : Starting index and sort_order}
        {--uploader= : Uploader user id, email, or login}
        {--replace : Deactivate existing offers in category that are not in this import batch}
        {--allow-local-app-url : Allow non-dry run even when APP_URL is localhost}
        {--dry-run : Preview import without writing files or database rows}';

    protected $description = 'Uploads local offer images with numbered names and seeds offers table rows.';

    public function __construct(
        private readonly MediaService $mediaService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $sourcePath = $this->resolveSourcePath((string) $this->option('source'));
        $categorySlug = trim((string) $this->option('category-slug'));
        $namePrefix = Str::slug((string) $this->option('name-prefix')) ?: 'offer';
        $startOrder = max((int) $this->option('start-order'), 1);
        $replace = (bool) $this->option('replace');
        $allowLocalAppUrl = (bool) $this->option('allow-local-app-url');
        $dryRun = (bool) $this->option('dry-run');
        $appUrl = (string) config('app.url', '');
        $isLocalAppUrl = Str::contains(strtolower($appUrl), ['localhost', '127.0.0.1']);

        if (!is_dir($sourcePath)) {
            $this->error("Source directory not found: {$sourcePath}");
            return self::FAILURE;
        }

        $category = OfferCategory::query()->where('slug', $categorySlug)->first();
        if (!$category) {
            $this->error("Offer category not found for slug: {$categorySlug}");
            return self::FAILURE;
        }

        $uploader = $this->resolveUploader($this->option('uploader'));
        if (!$uploader) {
            $this->error('No uploader user found. Provide --uploader or create an admin user first.');
            return self::FAILURE;
        }

        $files = $this->collectSourceFiles($sourcePath);
        if ($files->isEmpty()) {
            $this->warn("No supported images found in {$sourcePath}");
            return self::SUCCESS;
        }

        $existingOffersByImageBase = Offer::query()
            ->where('offer_category_id', $category->id)
            ->get()
            ->mapWithKeys(function (Offer $offer) {
                $baseName = $this->extractImageBaseName($offer->image);
                if ($baseName === '') {
                    return [];
                }
                return [strtolower($baseName) => $offer];
            });

        $this->info(sprintf(
            'Importing %d images into category "%s" (%s) using uploader "%s"%s',
            $files->count(),
            $category->title,
            $category->slug,
            $uploader->email ?? $uploader->login ?? (string) $uploader->id,
            $dryRun ? ' [DRY-RUN]' : '',
        ));

        if (!$dryRun && $isLocalAppUrl && !$allowLocalAppUrl) {
            $this->error(
                "APP_URL is '{$appUrl}'. Set a real production domain in APP_URL before importing, ".
                'or pass --allow-local-app-url explicitly.'
            );
            return self::FAILURE;
        }

        if (!$dryRun && $isLocalAppUrl && $allowLocalAppUrl) {
            $this->warn("APP_URL is currently '{$appUrl}'. Generated image URLs will use this host.");
        }

        $summary = [
            'processed' => 0,
            'uploaded' => 0,
            'reused_attachments' => 0,
            'created_offers' => 0,
            'updated_offers' => 0,
            'skipped_existing' => 0,
            'deactivated_offers' => 0,
            'failed' => 0,
        ];

        $expectedBaseNames = collect(range(0, $files->count() - 1))
            ->map(fn (int $index): string => strtolower("{$namePrefix}-" . ($startOrder + $index)));

        foreach ($files->values() as $index => $file) {
            $order = $startOrder + $index;
            $baseName = "{$namePrefix}-{$order}";
            $extension = strtolower($file->getExtension());
            $targetFileName = "{$baseName}.{$extension}";
            $summary['processed']++;
            $attachment = null;
            $uploadedNow = false;

            try {
                $existingOffer = $existingOffersByImageBase->get(strtolower($baseName));
                if ($existingOffer) {
                    if ($dryRun) {
                        $this->line("[DRY-RUN] Skip existing offer {$baseName} (id: {$existingOffer->id})");
                    } else {
                        $existingOffer->update([
                            'title' => $baseName,
                            'sort_order' => $order,
                            'is_active' => true,
                        ]);
                        $summary['updated_offers']++;
                        $this->line("Updated existing offer {$baseName} (id: {$existingOffer->id})");
                    }

                    $summary['skipped_existing']++;
                    continue;
                }

                $attachment = $this->findAttachmentBySlug($baseName);

                if ($attachment) {
                    $summary['reused_attachments']++;
                } elseif (!$dryRun) {
                    $uploaded = $this->mediaService->upload([
                        new UploadedFile(
                            $file->getRealPath(),
                            $targetFileName,
                            @mime_content_type($file->getRealPath()) ?: null,
                            null,
                            true,
                        ),
                    ], $uploader);

                    $attachment = $uploaded[0] ?? null;
                    if (!$attachment) {
                        throw new \RuntimeException("Upload failed for {$file->getFilename()}");
                    }

                    $uploadedNow = true;
                    $summary['uploaded']++;
                }

                if ($dryRun) {
                    $this->line("[DRY-RUN] Would upload + seed {$targetFileName} as offer {$baseName}");
                    continue;
                }

                $imageUrl = $attachment?->guid ?: null;
                if (!$imageUrl) {
                    throw new \RuntimeException("Unable to resolve URL for {$targetFileName}");
                }

                $offer = null;
                DB::transaction(function () use ($category, $baseName, $order, $imageUrl, &$offer): void {
                    $offer = Offer::updateOrCreate(
                        [
                            'offer_category_id' => $category->id,
                            'image' => $imageUrl,
                        ],
                        [
                            'title' => $baseName,
                            'is_active' => true,
                            'sort_order' => $order,
                        ]
                    );
                });

                if ($offer?->wasRecentlyCreated) {
                    $summary['created_offers']++;
                    $this->line("Created offer {$baseName} (id: {$offer->id})");
                } else {
                    $summary['updated_offers']++;
                    $this->line("Updated offer {$baseName} (id: {$offer?->id})");
                }

                $existingOffersByImageBase->put(strtolower($baseName), $offer);
            } catch (Throwable $e) {
                $summary['failed']++;
                if (!$dryRun && $uploadedNow && $attachment) {
                    try {
                        $this->mediaService->deleteAttachment($attachment);
                    } catch (Throwable) {
                        // Best effort cleanup to avoid orphaned attachments.
                    }
                }
                $this->error("Failed processing {$file->getFilename()}: {$e->getMessage()}");
            }
        }

        if ($replace) {
            if ($summary['failed'] > 0) {
                $this->warn('Replace mode skipped because there were import failures.');
            } else {
                $offersToDeactivate = Offer::query()
                    ->where('offer_category_id', $category->id)
                    ->get()
                    ->filter(function (Offer $offer) use ($expectedBaseNames): bool {
                        $baseName = strtolower($this->extractImageBaseName($offer->image));
                        return !$expectedBaseNames->contains($baseName);
                    })
                    ->values();

                if ($dryRun) {
                    $this->line("[DRY-RUN] Would deactivate {$offersToDeactivate->count()} offer(s) outside this import batch.");
                } else {
                    foreach ($offersToDeactivate as $offer) {
                        if ($offer->is_active) {
                            $offer->update(['is_active' => false]);
                            $summary['deactivated_offers']++;
                        }
                    }
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['processed', $summary['processed']],
                ['uploaded', $summary['uploaded']],
                ['reused_attachments', $summary['reused_attachments']],
                ['created_offers', $summary['created_offers']],
                ['updated_offers', $summary['updated_offers']],
                ['skipped_existing', $summary['skipped_existing']],
                ['deactivated_offers', $summary['deactivated_offers']],
                ['failed', $summary['failed']],
            ],
        );

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveSourcePath(string $source): string
    {
        if ($source === '') {
            return base_path('public/images/offers');
        }

        if (Str::startsWith($source, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $source) === 1) {
            return $source;
        }

        return base_path($source);
    }

    /**
     * @return Collection<int, SplFileInfo>
     */
    private function collectSourceFiles(string $sourcePath): Collection
    {
        $supported = $this->supportedImageExtensions();

        return collect(File::files($sourcePath))
            ->filter(function (SplFileInfo $file) use ($supported): bool {
                if (!$file->isFile()) {
                    return false;
                }

                $extension = strtolower($file->getExtension());
                return in_array($extension, $supported, true);
            })
            ->sortBy(
                fn (SplFileInfo $file): string => strtolower($file->getFilename()),
                SORT_NATURAL
            )
            ->values();
    }

    private function supportedImageExtensions(): array
    {
        return array_values(array_intersect(
            MediaService::allowedExtensions(),
            self::SUPPORTED_IMAGE_EXTENSIONS,
        ));
    }

    private function resolveUploader(mixed $input): ?User
    {
        $value = trim((string) ($input ?? ''));

        if ($value !== '') {
            if (ctype_digit($value)) {
                return User::query()->find((int) $value);
            }

            return User::query()
                ->where('email', $value)
                ->orWhere('login', $value)
                ->first();
        }

        return User::query()
            ->where('email', 'admin@meemmark.com')
            ->orWhere('login', 'admin')
            ->first()
            ?? User::query()->first();
    }

    private function findAttachmentBySlug(string $slug): ?Post
    {
        return Post::query()
            ->where('type', 'attachment')
            ->where('slug', $slug)
            ->first();
    }

    private function extractImageBaseName(string $image): string
    {
        $path = parse_url($image, PHP_URL_PATH);
        $candidate = is_string($path) && $path !== '' ? basename($path) : basename($image);
        $baseName = pathinfo($candidate, PATHINFO_FILENAME);

        return is_string($baseName) ? $baseName : '';
    }
}
