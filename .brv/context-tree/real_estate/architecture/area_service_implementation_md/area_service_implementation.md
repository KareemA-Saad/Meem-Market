## Relations
@real_estate/architecture/real_estate_module_architecture.md

## Raw Concept
**Task:**
Manage hierarchical areas and locations.

**Changes:**
- Implemented AreaService for hierarchical location management.
- Added circular reference and deletion protection logic.

**Files:**
- Modules/RealEstate/Services/AreaService.php

**Flow:**
Controller -> AreaService -> Database

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Services/AreaService.php

### Dependencies
- Laravel Cache
- Laravel DB transactions
- Area model hierarchy

### Features
- Hierarchical management (parent/child)
- Circular reference prevention
- Deletion protection (prevents deleting areas with children or linked entities)
- Tree structure generation
- Breadcrumb generation
- Search and statistics
