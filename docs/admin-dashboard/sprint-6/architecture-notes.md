# Architecture Notes - Sprint 6

## API Layer
- `MediaController` provides list, upload, show, update, delete, bulk delete, and edit endpoints.
- `per_page` is normalized to a safe range (`1..100`) for list stability.

## Validation
- Media request classes return standardized admin validation envelope with `code=VALIDATION_ERROR`.
- Upload constraints are driven by `MediaService::allowedExtensions()` for central type policy.

## Storage
- Attachments are persisted as `posts` records with `type=attachment`.
- Attachment metadata (`_wp_attached_file`, `_wp_attachment_metadata`, `_wp_attachment_image_alt`) is stored in `post_meta`.

## Behavior Notes
- Editing endpoint rejects non-raster files (for example PDF/document attachments) with a 422 response.
- Bulk endpoint currently supports delete semantics only.
