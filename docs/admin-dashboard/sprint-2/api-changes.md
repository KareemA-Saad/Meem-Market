# API Changes - Sprint 2

## Hardened Admin User APIs

### Validation
- `StoreUserRequest`, `UpdateUserRequest`, and `BulkUserRequest` now validate `role` against existing role slugs from `user_roles`.
- Validation responses now include:

```json
{
  "success": false,
  "message": "Validation failed.",
  "code": "VALIDATION_ERROR",
  "errors": {}
}
```

### Permissions
- `POST /api/v1/admin/users/bulk` now enforces `promote_users` capability when `action=change_role`.

### Integrity Safeguards
- `DELETE /api/v1/admin/users/{user}` rejects `reassign_to` equal to deleted user.
- `POST /api/v1/admin/users/bulk` rejects `reassign_to` if that user is included in `user_ids`.

### Observability
- Added structured logs for user create, update, delete, bulk delete, and bulk role change flows.
