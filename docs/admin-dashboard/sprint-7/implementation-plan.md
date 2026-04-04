# Sprint 7 Implementation Plan - Comments + Settings

## Objectives
- Complete admin moderation APIs for comments.
- Complete admin section-based management APIs for settings.
- Keep RBAC, response envelope, and validation behavior aligned with prior sprints.

## Scope
- Resources: Comments, Settings.
- Included: comment moderation/status workflows, settings section read/update, settings section validation, tests, and Swagger updates.
- Excluded: admin frontend screens.

## Security
- Endpoints are protected by `auth:sanctum`.
- Comments route group uses `can_do:moderate_comments`.
- Settings route group uses `can_do:manage_options`.

## Quality Gates
- Feature tests for auth and capability checks.
- Feature tests for comment CRUD/moderation/reply/bulk flows.
- Feature tests for settings read/update/validation and unknown section handling.
- Swagger docs regenerated and include comments/settings admin endpoints.
