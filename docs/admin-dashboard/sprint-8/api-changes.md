# API Changes - Sprint 8

## Dashboard Updates
- `GET /api/v1/admin/dashboard/stats`
  - Added `business_summary` block (countries, branches, offers, careers, unread contacts).
  - Added `homepage_summary` block (active/total content counts + readiness flag).
- `POST /api/v1/admin/dashboard/quick-draft`
  - Validation now follows admin-standard envelope with `code=VALIDATION_ERROR`.

## New Homepage Admin Endpoints
- `GET /api/v1/admin/homepage/overview`
- `GET /api/v1/admin/homepage/preview`

### Sections
- `GET /api/v1/admin/homepage/sections`
- `POST /api/v1/admin/homepage/sections`
- `GET /api/v1/admin/homepage/sections/{id}`
- `PUT /api/v1/admin/homepage/sections/{id}`
- `DELETE /api/v1/admin/homepage/sections/{id}`
- `POST /api/v1/admin/homepage/sections/bulk`
- `PUT /api/v1/admin/homepage/sections/reorder`

### Partners
- `GET /api/v1/admin/homepage/partners`
- `POST /api/v1/admin/homepage/partners`
- `GET /api/v1/admin/homepage/partners/{id}`
- `PUT /api/v1/admin/homepage/partners/{id}`
- `DELETE /api/v1/admin/homepage/partners/{id}`
- `POST /api/v1/admin/homepage/partners/bulk`
- `PUT /api/v1/admin/homepage/partners/reorder`

### Features
- `GET /api/v1/admin/homepage/features`
- `POST /api/v1/admin/homepage/features`
- `GET /api/v1/admin/homepage/features/{id}`
- `PUT /api/v1/admin/homepage/features/{id}`
- `DELETE /api/v1/admin/homepage/features/{id}`
- `POST /api/v1/admin/homepage/features/bulk`
- `PUT /api/v1/admin/homepage/features/reorder`
