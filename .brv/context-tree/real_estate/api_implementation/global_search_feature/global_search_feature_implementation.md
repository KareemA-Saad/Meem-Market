## Relations
@real_estate/api_implementation/phase_2_controllers_and_api_endpoints.md
@real_estate/architecture/real_estate_module_architecture.md

## Raw Concept
**Task:**
Implement Unified Global Search for Real Estate module

**Changes:**
- Added globalSearch method to SearchService.php
- Added searchPropertiesForGlobal, searchCompoundsForGlobal, searchAreasForGlobal, searchDevelopersForGlobal helper methods
- Implemented calculateRelevanceScore and cache management logic
- Added UnifiedSearchResultResource for standardized output
- Registered /api/v1/tenant/{tenant}/realestate/search route

**Files:**
- Modules/RealEstate/Services/SearchService.php
- Modules/RealEstate/Transformers/UnifiedSearchResultResource.php
- Modules/RealEstate/Http/Controllers/FrontendSearchController.php
- docs/GLOBAL_SEARCH.md
- docs/GLOBAL_SEARCH_USAGE_GUIDE.md

**Flow:**
Request -> FrontendSearchController@global -> SearchService@globalSearch -> Parallel Entity Searches (Properties, Compounds, Areas, Developers) -> Calculate Relevance -> Merge & Sort -> Paginate -> Transform via UnifiedSearchResultResource -> Response

**Timestamp:** 2026-02-10

## Narrative
### Structure
- Endpoint: GET /api/v1/tenant/{tenant}/realestate/search
- Service: Modules/RealEstate/Services/SearchService.php
- Resource: Modules/RealEstate/Transformers/UnifiedSearchResultResource.php
- Route Name: api.v1.tenant.realestate.search.global

### Dependencies
SearchService.php, UnifiedSearchResultResource.php, FrontendSearchController.php

### Features
- Multi-entity search (Properties, Compounds, Areas, Developers)
- 13+ filters (q, purpose, property_type_id, min_price, max_price, bedrooms, bathrooms, area_id, developer_id, amenities, finishing, entity_type, per_page, page, sort)
- Relevance-based scoring algorithm (Exact match: 100, Starts with: 80, Contains: 60, Description: 40, Bonuses for featured/recent/images)
- Redis/Memcached tag-based caching with 5-min TTL
- Unified response format via UnifiedSearchResultResource
