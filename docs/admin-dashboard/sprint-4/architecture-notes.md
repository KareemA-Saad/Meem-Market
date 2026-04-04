# Architecture Notes - Sprint 4

## Domain Updates
- `Career` now supports explicit ordering via `sort_order`.
- `ContactMessage` now supports inbox status with `is_read` and `read_at`.

## API Layer
- Added:
  - `CareerAdminController`
  - `ContactMessageAdminController`
- Added request validation for create/update/bulk/reorder workflows.
- Added admin resources for both modules.

## Operational Behavior
- Career bulk/reorder actions are transactional.
- Contact bulk actions are transactional and support moderation-style workflows.
- Structured logs were added for mutation endpoints.

## Backward Compatibility
- Public career/contact endpoints remain available.
- Public careers listing now uses explicit ordering (`sort_order`, then `id`).
