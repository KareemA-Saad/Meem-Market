# Architecture Notes - Sprint 8

## Business Alignment
- Dashboard now exposes operational signals needed by stakeholders:
  - Core publishing counts.
  - Offer/career/contact health.
  - Homepage publish-readiness checks.
- Homepage publishing requires active content across all required homepage modules.

## Data Model
- Added `is_active` to `competitive_features` to support controlled publishing.
- Added indexes for homepage listing patterns:
  - `sections_active_sort_idx`
  - `partners_active_sort_idx`
  - `features_active_sort_idx`

## API Behavior
- Public `/api/v1/home` now serves only active competitive features.
- Admin homepage preview endpoint returns the same active-only composition used by public home.
- Bulk and reorder endpoints are standardized to existing admin patterns.

## Validation and Safety
- Quick draft now uses form request validation with standardized JSON error envelope.
- Homepage entity requests use consistent validation and bulk action constraints.
