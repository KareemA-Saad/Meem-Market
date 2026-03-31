# Offers Image Upload + Seeding Implementation Plan

## 1) Current-State Analysis

### Offers domain model
- `offers` table (`database/migrations/2026_02_16_100004_create_offers_table.php`) has:
  - `id`
  - `offer_category_id` (FK -> `offer_categories.id`)
  - `title` (nullable)
  - `image` (required string URL/path consumed by API as-is)
  - `is_active` (bool, default `true`)
  - `sort_order` (int, default `0`)
  - timestamps
- `App\Models\Offer` is fillable for all above mutable fields and exposes `active()` + `ordered()` scopes.
- Public API returns `image` directly through `OfferResource`.

### Existing offer seeding
- `database/seeders/OfferSeeder.php` currently seeds offers with hardcoded remote URLs under `https://meem-market.com/wp-content/uploads/...`.
- Upsert key is currently (`offer_category_id`, `image`).

### Upload service behavior in this project
- Upload API endpoint: `POST /api/v1/admin/media/upload` (auth + `upload_files` capability).
- Internally handled by `App\Services\MediaService`:
  - stores files on `Storage::disk('public')` (`storage/app/public`)
  - default path format: `uploads/{Y}/{m}/filename.ext` (controlled by `uploads_use_yearmonth_folders` option)
  - creates an attachment row in `posts` (`type = attachment`) and metadata rows in `post_meta`:
    - `_wp_attached_file`
    - `_wp_attachment_metadata`
    - `_wp_attachment_image_alt`
  - generates resized variants for raster images
  - public URL is generated as `Storage::disk('public')->url($relativePath)` -> `{APP_URL}/storage/...`

### Input assets identified
- Source folder exists: `public/images/offers`
- Contains a batch of `.webp` offer images (Arabic filenames), ready for ingestion.

## 2) Gap Analysis / Key Decisions

1. URL format mismatch risk:
- Legacy seed data uses `.../wp-content/uploads/...`
- Current Laravel upload flow produces `.../storage/uploads/...`
- Decision needed: accept new canonical `/storage/uploads/...` URLs for offers (recommended).

2. Idempotency:
- Re-running import should not create duplicate offers or duplicate media attachments.
- We need a stable upsert key strategy independent from generated file names.

3. Ownership of uploaded media:
- `MediaService::upload()` requires a valid `User` as author.
- Script must resolve an uploader user (default: admin user, fallback: first user).

## 3) Recommended Implementation Strategy

Implement a dedicated Artisan command (not only a Seeder) that performs:
1. local file discovery
2. upload via the existing `MediaService` (same production logic)
3. idempotent upsert into `offers`

Why command-first:
- deterministic operational workflow for existing environments
- supports dry-run and safe re-execution
- avoids coupling DB seeding with filesystem/date-dependent uploads

## 4) Planned Deliverables

1. `app/Console/Commands/ImportOfferImagesCommand.php`
- Signature (proposed):
  - `offers:import-images`
  - options:
    - `--source=public/images/offers`
    - `--category-slug=coming-winter-offers`
    - `--title-prefix="Winter Offer"`
    - `--start-order=1`
    - `--dry-run`
    - `--replace` (optional behavior for existing category rows)

2. Registration
- Register command in console bootstrap/provider so it appears in `php artisan list`.

3. Optional seeder bridge (if needed)
- Lightweight seeder that can call command/service for controlled environments only.
- Keep default `DatabaseSeeder` stable unless explicitly requested.

4. Documentation
- Add runbook section in README or dedicated doc with exact command usage and examples.

## 5) Command Flow (Detailed)

1. Validate preconditions
- ensure source directory exists and has supported image files
- resolve target `OfferCategory` by slug (fallback optional by title)
- resolve uploader `User`

2. Build deterministic import set
- collect files with extensions supported by upload service (`jpg,jpeg,png,gif,webp,svg`)
- sort by filename ascending for stable `sort_order`

3. Upload step
- for each local file:
  - create `UploadedFile` instance from absolute path
  - call `MediaService::upload([$file], $user)`
  - extract canonical public URL (attachment `guid` or `MediaService::getUrl()`)

4. Offer upsert step
- upsert `offers` row with:
  - `offer_category_id`
  - `image` = uploaded URL
  - `title` = `{title_prefix} {n}` (or parsed filename strategy)
  - `sort_order` = sequential
  - `is_active` = true
- idempotency strategy:
  - primary: skip if same image URL already linked to category
  - with `--replace`: delete or deactivate existing offers in category before insert/upsert

5. Transaction & reporting
- wrap DB writes in transaction per run
- provide summary output:
  - discovered files
  - uploaded count
  - skipped count
  - offers created/updated
  - failures (with file names)

## 6) Safety and Operational Considerations

- Use `--dry-run` for first execution in production.
- Preserve existing offers unless `--replace` is explicitly passed.
- Log each imported file -> offer ID mapping for rollback/audit.
- If any upload fails, continue and report; do not silently swallow failures.

## 7) Validation Plan

1. Functional checks
- run command on local/staging with a small subset
- verify new rows exist in `offers` with expected `sort_order`
- verify `image` URLs are reachable
- verify records appear correctly in `GET /api/v1/offers`

2. Media integrity checks
- verify attachment row exists in `posts` (`type=attachment`)
- verify `_wp_attached_file` and `_wp_attachment_metadata` are created

3. Idempotency checks
- run command twice with same inputs
- confirm no duplicated offer rows (and expected behavior with/without `--replace`)

## 8) Open Items Before Coding

1. Confirm target category slug (default proposed: `coming-winter-offers`).
2. Confirm title strategy:
- generic numbered titles, or
- Arabic title derived from filename/metadata.
3. Confirm whether existing offers in that category should be preserved or replaced.

## 9) Execution Sequence (After Approval)

1. Implement command + registration.
2. Add concise docs with sample invocations.
3. Run dry-run locally.
4. Run real import locally and verify API output.
5. Share command output summary and resulting DB/API state.
