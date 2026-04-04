# Sprint 4 Implementation Plan - Careers + Contact Messages

## Objectives
- Deliver admin management APIs for careers and contact messages.
- Align validation, response envelope, RBAC, and logging with prior sprints.
- Add practical inbox workflow for contact messages (read/unread + bulk actions).

## Scope
- Resource 1: Careers
- Resource 2: Contact Messages
- Included: API endpoints, form requests, admin resources, migrations, tests, and docs.
- Excluded: admin UI pages.

## Security
- Endpoints are under `auth:sanctum`.
- Route groups use `can_do:manage_options`.

## Quality Gates
- Feature coverage for auth, capability checks, CRUD/bulk/reorder (careers), and inbox moderation paths (contact messages).
- Swagger generation includes all Sprint 4 admin endpoints.
