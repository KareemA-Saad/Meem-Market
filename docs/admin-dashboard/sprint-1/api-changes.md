# API Changes - Sprint 1

## New Admin Endpoints

### Offer Categories
- `GET /api/v1/admin/offer-categories`
  - Query params: `branch_id`, `is_active`, `search`, `start_date_from`, `start_date_to`, `sort_by`, `sort_dir`, `per_page`
- `POST /api/v1/admin/offer-categories`
  - Multipart payload: `branch_id`, `title`, optional `slug`, optional `cover_image`, optional `start_date`, optional `end_date`, optional `is_active`, optional `sort_order`
- `GET /api/v1/admin/offer-categories/{id}`
- `PUT /api/v1/admin/offer-categories/{id}`
  - Supports updating fields and optional new `cover_image`
- `DELETE /api/v1/admin/offer-categories/{id}`
- `POST /api/v1/admin/offer-categories/bulk`
  - Payload: `{ "action": "delete|activate|deactivate", "ids": [1,2] }`
- `PUT /api/v1/admin/offer-categories/reorder`
  - Payload: `{ "items": [{ "id": 1, "sort_order": 10 }] }`

### Offers
- `GET /api/v1/admin/offers`
  - Query params: `offer_category_id`, `branch_id`, `is_active`, `search`, `sort_by`, `sort_dir`, `per_page`
- `POST /api/v1/admin/offers`
  - Multipart payload: `offer_category_id`, optional `title`, required `image`, optional `is_active`, optional `sort_order`
- `GET /api/v1/admin/offers/{id}`
- `PUT /api/v1/admin/offers/{id}`
  - Supports updating fields and optional new `image`
- `DELETE /api/v1/admin/offers/{id}`
- `POST /api/v1/admin/offers/bulk`
  - Payload: `{ "action": "delete|activate|deactivate", "ids": [1,2] }`
- `PUT /api/v1/admin/offers/reorder`
  - Payload: `{ "items": [{ "id": 1, "sort_order": 10 }] }`

## Error Envelope (Sprint 1)
New sprint endpoints include:

```json
{
  "success": false,
  "message": "Error message",
  "code": "ERROR_CODE"
}
```

## OpenAPI
- Swagger annotations added for all new endpoints.
- Regenerated file: `storage/api-docs/api-docs.json`.
