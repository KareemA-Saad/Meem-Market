# Architecture Notes - Sprint 3

## Domain Relationships
- `Country` has many `Branch`.
- `Branch` belongs to `Country` and has many `OfferCategory`.

## API Layer
- Added:
  - `CountryAdminController`
  - `BranchAdminController`
- Added admin resources for normalized admin payloads:
  - `App\Http\Resources\V1\Admin\CountryResource`
  - `App\Http\Resources\V1\Admin\BranchResource`

## Security
- All endpoints are under `auth:sanctum`.
- Both module route groups use capability middleware: `can_do:manage_options`.

## Data Integrity
- Slug generation supports auto-fallback and uniqueness checks.
- Delete operations preserve relational integrity via cascade constraints.
- Bulk and reorder operations run inside transactions.

## Observability
- Structured logs added for create/update/delete/bulk/reorder on countries and branches.
