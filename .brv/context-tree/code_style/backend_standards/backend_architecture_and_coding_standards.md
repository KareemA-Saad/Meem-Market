## Relations
@tenancy/architecture/architecture_overview.md

## Raw Concept
**Task:**
Define backend architecture and coding standards.

**Changes:**
- Established DDD and Clean Architecture as core principles.
- Defined 6 Universal Backend Laws.
- Mandated pre-coding checklist for safety, security, architecture, performance, and debuggability.

**Files:**
- .cursor/rules/backend-rules.mdc

**Flow:**
Request -> Validation (DTO) -> Controller (Thin) -> Action (Logic/Transaction) -> API Resource -> Response

**Timestamp:** 2026-01-14

## Narrative
### Structure
- Actions/UseCases for business logic.\n- Readonly DTOs for data transfer.\n- API Resources for JSON output (never raw models).\n- Constructor Injection over Facades.

### Dependencies
Requires PHP 8.2+ for readonly DTOs and Laravel framework for Action/Resource patterns.

### Features
- Atomic Consistency: Multi-step writes must be in transactions.\n- Input Validation: All input validated via DTOs/Schemas.\n- Feature Encapsulation: Use Single Action classes (not God Services).\n- Type Safety: Strict typing with declare(strict_types=1).\n- Observability: Tests must log data diffs on failure.\n- Tenant Isolation: Explicit tenant_id filtering on all queries.
