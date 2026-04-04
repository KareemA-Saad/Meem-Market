# API Changes - Sprint 4

## New Admin Endpoints

### Careers
- `GET /api/v1/admin/careers`
- `POST /api/v1/admin/careers`
- `GET /api/v1/admin/careers/{id}`
- `PUT /api/v1/admin/careers/{id}`
- `DELETE /api/v1/admin/careers/{id}`
- `POST /api/v1/admin/careers/bulk`
- `PUT /api/v1/admin/careers/reorder`

### Contact Messages
- `GET /api/v1/admin/contact-messages`
- `GET /api/v1/admin/contact-messages/{id}`
- `PUT /api/v1/admin/contact-messages/{id}` (set `is_read`)
- `DELETE /api/v1/admin/contact-messages/{id}`
- `POST /api/v1/admin/contact-messages/bulk`

## Data Changes
- `careers` table: added `sort_order` + index for active/sort listing.
- `contact_messages` table: added `is_read`, `read_at` + index for inbox filtering.

## Notes
- Validation errors follow `code: VALIDATION_ERROR`.
- Bulk actions:
  - Careers: `delete`, `activate`, `deactivate`
  - Contact messages: `delete`, `mark_read`, `mark_unread`
