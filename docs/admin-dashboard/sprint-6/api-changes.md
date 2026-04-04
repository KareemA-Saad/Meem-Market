# API Changes - Sprint 6

## Admin Endpoints (Media Library)
- `GET /api/v1/admin/media`
- `POST /api/v1/admin/media/upload`
- `GET /api/v1/admin/media/{id}`
- `PUT /api/v1/admin/media/{id}`
- `DELETE /api/v1/admin/media/{id}`
- `POST /api/v1/admin/media/bulk`
- `POST /api/v1/admin/media/{id}/edit`

## Payload Notes
- Upload accepts multipart form-data (`files[]`) and optional `attached_to`.
- Metadata update supports `title`, `caption`, `alt_text`, `description`.
- Bulk supports `action=delete` with `media_ids`.
- Edit endpoint supports actions (`crop`, `rotate`, `flip`, `scale`) and validates required params.

## Response Notes
- Validation errors use admin-standard envelope:
  - `success: false`
  - `message: "Validation failed."`
  - `code: "VALIDATION_ERROR"`
