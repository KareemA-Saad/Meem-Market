# Sprint 2 Implementation Plan - Admin Users

## Objectives
- Harden admin user management APIs for production use.
- Enforce capability checks for sensitive role-change paths.
- Ensure request validation rejects invalid role slugs with clear errors.
- Add automated feature coverage for CRUD and bulk user operations.

## Scope
- Resource: Admin Users
- Included: validation hardening, permission hardening, reassignment safeguards, structured logging, feature tests, and sprint docs.
- Excluded: admin UI work, role editor UX, and non-user resources.

## Endpoint Focus
- `GET /api/v1/admin/users`
- `POST /api/v1/admin/users`
- `GET /api/v1/admin/users/{user}`
- `PUT /api/v1/admin/users/{user}`
- `DELETE /api/v1/admin/users/{user}`
- `POST /api/v1/admin/users/bulk`

## Quality Gates
- Feature tests for auth/capability enforcement, CRUD, and bulk role/delete actions.
- Validation failures must return `VALIDATION_ERROR`.
- Bulk role change must require `promote_users`.
- Deletion reassignment must reject invalid targets.
