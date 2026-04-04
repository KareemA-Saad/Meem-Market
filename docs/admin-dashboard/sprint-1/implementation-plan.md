# Sprint 1 Implementation Plan - Admin Offers + Offer Categories

## Objectives
- Deliver full admin CRUD APIs for offers and offer categories.
- Enforce RBAC with `manage_offers` and `manage_offer_categories` capabilities.
- Support bulk operations and reorder operations for both resources.
- Keep request validation, response envelopes, and documentation aligned with existing admin API standards.

## Scope
- Resource 1: Offer Categories
- Resource 2: Offers
- Included: list, create, show, update, delete, bulk, reorder, OpenAPI docs, tests, indexes, structured logs.
- Excluded: media-id linking contract, admin UI work, non-offer resources.

## Endpoint Plan
- `GET /api/v1/admin/offer-categories`
- `POST /api/v1/admin/offer-categories`
- `GET /api/v1/admin/offer-categories/{id}`
- `PUT /api/v1/admin/offer-categories/{id}`
- `DELETE /api/v1/admin/offer-categories/{id}`
- `POST /api/v1/admin/offer-categories/bulk`
- `PUT /api/v1/admin/offer-categories/reorder`
- `GET /api/v1/admin/offers`
- `POST /api/v1/admin/offers`
- `GET /api/v1/admin/offers/{id}`
- `PUT /api/v1/admin/offers/{id}`
- `DELETE /api/v1/admin/offers/{id}`
- `POST /api/v1/admin/offers/bulk`
- `PUT /api/v1/admin/offers/reorder`

## Quality Gates
- Feature tests for authentication, authorization, CRUD, validation, list/filter/sort, bulk, reorder, and upload contract.
- Swagger generation must include all new endpoints in `storage/api-docs/api-docs.json`.
- Full test suite must pass.
