## Relations
@real_estate/architecture/real_estate_module_architecture.md

## Raw Concept
**Task:**
Centralize property management business logic.

**Changes:**
- Implemented PropertyService for property business logic.
- Integrated Spatie QueryBuilder for dynamic filtering.
- Added caching for featured lists and statistics.

**Files:**
- Modules/RealEstate/Services/PropertyService.php

**Flow:**
Controller -> PropertyService -> (QueryBuilder/Cache/Eloquent) -> Database

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Services/PropertyService.php

### Dependencies
- Spatie QueryBuilder
- Laravel Cache (tagging support)
- Laravel DB transactions
- Relation to Property, Compound, Area, Developer, PropertyType, Amenity, Image models

### Features
- Advanced filtering & sorting (Spatie)
- CRUD with automatic slug generation and uniqueness
- Cache management (featured lists, stats)
- Bulk actions (publish, delete, feature)
- Statistics (total, active, avg price, etc.)
- Similar properties logic
