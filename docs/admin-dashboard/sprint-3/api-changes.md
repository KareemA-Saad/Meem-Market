# API Changes - Sprint 3

## New Admin Endpoints

### Countries
- `GET /api/v1/admin/countries`
- `POST /api/v1/admin/countries`
- `GET /api/v1/admin/countries/{id}`
- `PUT /api/v1/admin/countries/{id}`
- `DELETE /api/v1/admin/countries/{id}`
- `POST /api/v1/admin/countries/bulk`
- `PUT /api/v1/admin/countries/reorder`

### Branches
- `GET /api/v1/admin/branches`
- `POST /api/v1/admin/branches`
- `GET /api/v1/admin/branches/{id}`
- `PUT /api/v1/admin/branches/{id}`
- `DELETE /api/v1/admin/branches/{id}`
- `POST /api/v1/admin/branches/bulk`
- `PUT /api/v1/admin/branches/reorder`

## Endpoint Notes
- Slugs are auto-generated when omitted and validated for uniqueness.
- Bulk actions support: `delete`, `activate`, `deactivate`.
- Reorder endpoints accept `{ items: [{ id, sort_order }] }`.
- Validation failures return `code: VALIDATION_ERROR`.
