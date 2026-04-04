# API Changes - Sprint 7

## Admin Endpoints (Comments)
- `GET /api/v1/admin/comments`
- `POST /api/v1/admin/comments/bulk`
- `GET /api/v1/admin/comments/{id}`
- `PUT /api/v1/admin/comments/{id}`
- `DELETE /api/v1/admin/comments/{id}`
- `POST /api/v1/admin/comments/{id}/approve`
- `POST /api/v1/admin/comments/{id}/unapprove`
- `POST /api/v1/admin/comments/{id}/spam`
- `POST /api/v1/admin/comments/{id}/trash`
- `POST /api/v1/admin/comments/{id}/restore`
- `POST /api/v1/admin/comments/{id}/reply`

## Admin Endpoints (Settings)
- `GET /api/v1/admin/settings/{section}`
- `PUT /api/v1/admin/settings/{section}`

## Payload Notes
- Comment bulk actions: `approve`, `unapprove`, `spam`, `trash`, `delete`.
- Settings `section` supports: `general`, `writing`, `reading`, `discussion`, `media`, `permalinks`, `privacy`.
- Settings updates are validated per section using dedicated request rule sets.

## Response Notes
- Validation failures in comments/settings use admin-standard envelope with `code=VALIDATION_ERROR`.
