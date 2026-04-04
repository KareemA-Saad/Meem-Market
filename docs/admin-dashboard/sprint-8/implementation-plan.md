# Sprint 8 Implementation Plan - Admin Dashboard & Homepage Management

## Objectives
- Finalize Phase 1 with business-focused dashboard visibility and homepage governance.
- Provide full admin management for homepage sections, partners, and competitive features.
- Add homepage readiness/preview endpoints to reduce publishing mistakes.

## Scope
- Modules: Admin Dashboard, Homepage Management.
- Included: dashboard business/homepage summary, quick-draft validation hardening, homepage overview/preview, sections/partners/features CRUD + bulk + reorder, tests, and docs.
- Excluded: admin frontend UI implementation.

## Security
- Endpoints are protected by `auth:sanctum`.
- Dashboard uses `read` and `edit_posts` capabilities.
- Homepage management routes use `manage_options` capability.

## Quality Gates
- Feature tests for auth, RBAC, dashboard counters, quick draft validation, homepage CRUD/bulk/reorder, and preview/overview behavior.
- Full admin feature suite remains green.
