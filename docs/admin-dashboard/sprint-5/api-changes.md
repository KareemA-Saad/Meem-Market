# API Changes - Sprint 5

## New Admin Endpoints (Sliders)
- `GET /api/v1/admin/sliders`
- `POST /api/v1/admin/sliders`
- `GET /api/v1/admin/sliders/{id}`
- `PUT /api/v1/admin/sliders/{id}`
- `POST /api/v1/admin/sliders/{id}` (multipart-safe update)
- `DELETE /api/v1/admin/sliders/{id}`
- `POST /api/v1/admin/sliders/bulk`
- `PUT /api/v1/admin/sliders/reorder`

## Payload Notes
- Create/update use multipart form-data for image upload.
- Added `media_type` key with enum values: `image`, `video` (defaults to `image` when omitted).
- Update supports metadata-only JSON updates without image replacement.
- Bulk actions: `delete`, `activate`, `deactivate`.
- Reorder payload: `{ "items": [{ "id": 1, "sort_order": 2 }] }`.

## Data Layer
- Added slider indexes for admin listing:
  - `sliders_active_sort_idx`
  - `sliders_sort_idx`
- Added media classification/indexing:
  - `media_type` column on `sliders`
  - `sliders_media_active_sort_idx`
