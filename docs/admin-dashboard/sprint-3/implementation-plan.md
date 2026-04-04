# Sprint 3 Implementation Plan - Countries + Branches

## Objectives
- Deliver full admin management APIs for countries and branches.
- Keep response envelope, validation behavior, and RBAC style consistent with prior sprints.
- Cover CRUD, bulk actions, reorder, filtering, and sort flows with feature tests.

## Scope
- Resource 1: Countries
- Resource 2: Branches
- Included: list, create, show, update, delete, bulk, reorder, Swagger annotations, tests, and sprint docs.
- Excluded: UI work and non-geographic modules.

## Security
- Endpoints are protected by `auth:sanctum`.
- RBAC uses `can_do:manage_options` for both modules.

## Quality Gates
- Feature tests for auth, capability checks, CRUD, bulk, reorder, and validation.
- Admin feature suite must pass with no regressions.
