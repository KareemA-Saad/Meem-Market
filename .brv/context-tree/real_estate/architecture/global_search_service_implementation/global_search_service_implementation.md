## Relations
@real_estate/api_implementation/global_search_feature/global_search_feature_implementation.md
@real_estate/architecture/search_service_implementation_md/search_service_implementation.md

## Raw Concept
**Task:**
Implement backend service logic for Unified Global Search

**Changes:**
- Added globalSearch(array $params) method for multi-entity querying
- Implemented searchPropertiesForGlobal, searchCompoundsForGlobal, searchAreasForGlobal, searchDevelopersForGlobal helper methods
- Implemented calculateRelevanceScore() with 8-tier logic
- Added generateGlobalSearchCacheKey() and invalidateGlobalSearchCache()

**Files:**
- Modules/RealEstate/Services/SearchService.php
- docs/GLOBAL_SEARCH_IMPLEMENTATION_PLAN.md

**Flow:**
Params -> Cache Check -> Parallel Entity Queries -> Apply Filters -> Calculate Relevance -> Merge -> Sort -> Paginate -> Cache Store -> Return

**Timestamp:** 2026-02-10

## Narrative
### Structure
Modules/RealEstate/Services/SearchService.php

### Dependencies
Property, Compound, Area, Developer entities, Redis/Memcached (optional for tagging)

### Features
- Parallel entity-specific searches (Properties, Compounds, Areas, Developers)
- 8-tier relevance scoring algorithm (+100 exact, +80 starts-with, +60 contains, etc.)
- Tag-based caching with 5-minute TTL and automatic invalidation
- Entity-type filtering logic
- Post-merge pagination and sorting (relevance_score DESC, created_at DESC)
