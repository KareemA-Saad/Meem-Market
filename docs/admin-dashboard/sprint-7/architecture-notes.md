# Architecture Notes - Sprint 7

## Comment Moderation
- `CommentController` supports list/show/update/delete plus moderation status transitions and reply creation.
- Comment list includes status aggregates in response meta.
- `per_page` input is clamped to `1..100` for stable pagination behavior.

## Settings Management
- `SettingsController` keeps a strict section-to-option-key map.
- Section updates now validate input using dedicated section request classes.
- Settings writes are normalized to canonical storage forms (string booleans/integers) and cast back on output.

## Validation Envelope
- Comment/media/settings request validation failures are standardized to:
  - `success: false`
  - `message: "Validation failed."`
  - `code: "VALIDATION_ERROR"`
  - `errors: {...}`
