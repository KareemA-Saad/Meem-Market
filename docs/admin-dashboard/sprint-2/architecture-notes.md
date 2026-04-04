# Architecture Notes - Sprint 2

## Domain and Capability Model
- User capabilities are resolved from role definitions in `options.user_roles`.
- Each user's assigned role remains stored in `user_meta` under `wp_capabilities`.
- Role mutation paths are guarded with `promote_users`.

## Reliability Improvements
- User creation and profile updates now use transaction boundaries for multi-step writes.
- Reassignment safeguards protect against invalid self/loop reassignment during delete operations.

## Security Improvements
- Bulk `change_role` now checks `promote_users` at runtime.
- Invalid role slugs are blocked at validation layer before service execution.

## Observability
- Added structured logs for:
  - `admin.users.created`
  - `admin.users.updated`
  - `admin.users.deleted`
  - `admin.users.bulk_deleted`
  - `admin.users.bulk_role_changed`
