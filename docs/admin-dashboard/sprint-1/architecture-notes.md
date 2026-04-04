# Architecture Notes - Sprint 1

## Domain Relationships
- `Country` has many `Branch`.
- `Branch` has many `OfferCategory`.
- `OfferCategory` has many `Offer`.
- `Offer` belongs to `OfferCategory`.

## API Layer
- New controllers:
  - `OfferCategoryAdminController`
  - `OfferAdminController`
- Both extend `ApiController` and use the standard admin success envelope.
- Error responses for sprint-1 endpoints include a machine-readable `code`.

## Security
- All routes are under `auth:sanctum`.
- Capability guard mapping:
  - offer categories: `can_do:manage_offer_categories`
  - offers: `can_do:manage_offers`

## Validation and Integrity
- Form requests validate payload structure and file constraints.
- Category slug uniqueness is enforced per branch in controller logic.
- Date range integrity is validated (`end_date >= start_date`).
- Multi-step write paths (create/update with uploads, bulk, reorder) use transactions.

## Performance and Data Access
- Added composite indexes on `offers` and `offer_categories` for admin list/filter/sort access patterns.
- List endpoints use selective eager-loading to avoid N+1 behavior.

## Logging
- Structured logs were added for create/update/delete/bulk/reorder operations with actor and resource context.
