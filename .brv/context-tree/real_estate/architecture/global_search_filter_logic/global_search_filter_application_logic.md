## Relations
@real_estate/api_implementation/global_search_feature/global_search_feature_implementation.md
@real_estate/architecture/global_search_service_implementation/global_search_service_implementation.md

## Raw Concept
**Task:**
Implement intelligent filter application for Global Search feature

**Changes:**
- Implemented conditional filter application logic in SearchService helper methods
- Added recursive area ID resolution for hierarchical filtering
- Implemented price range overlap logic for Compound entities
- Added AND-logic pivot filtering for amenities

**Files:**
- Modules/RealEstate/Services/SearchService.php
- docs/GLOBAL_SEARCH_IMPLEMENTATION_PLAN.md

**Flow:**
Filter Params -> Entity Query -> Check Filter Applicability -> Apply Scoped Logic -> Result Set

**Timestamp:** 2026-02-10

## Narrative
### Structure
Modules/RealEstate/Services/SearchService.php

### Dependencies
SearchService, Property, Compound, Area, Developer entities

### Features
- Context-aware filter application across entity types
- Multi-field text search (q) mapping to entity-specific fields
- Entity-specific filter scoping (e.g., purpose only for properties)
- Shared filters with distinct logic (e.g., price for properties vs. compounds)
- Recursive area hierarchy support (area_id)
- Pivot-based relation filtering (amenities)
- Graceful handling of inapplicable filter combinations
