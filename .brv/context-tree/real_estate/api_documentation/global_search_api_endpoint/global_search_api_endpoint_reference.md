## Relations
@real_estate/api_implementation/global_search_feature_implementation.md
@real_estate/api_routes/real_estate_api_structure_md/real_estate_api_structure.md

## Raw Concept
**Task:**
Implement and document Unified Global Search API endpoint

**Changes:**
- Implemented global() method in FrontendSearchController with 13-param validation
- Added globalSearch() in SearchService with parallel entity querying logic
- Implemented calculateRelevanceScore() based on 8-tier algorithm
- Added tag-based cache invalidation for Redis/Memcached drivers
- Integrated OpenAPI/Swagger documentation for the endpoint

**Files:**
- Modules/RealEstate/Http/Controllers/FrontendSearchController.php
- Modules/RealEstate/Services/SearchService.php
- Modules/RealEstate/Transformers/UnifiedSearchResultResource.php
- docs/GLOBAL_SEARCH.md
- docs/GLOBAL_SEARCH_USAGE_GUIDE.md

**Flow:**
Request -> FrontendSearchController@global -> Validation -> SearchService@globalSearch -> Parallel Entities Search -> Scoring -> Merging & Sorting -> Pagination -> UnifiedSearchResultResource -> JSON Response

**Timestamp:** 2026-02-10

## Narrative
### Structure
- Endpoint: GET /api/v1/tenant/{tenant}/realestate/search
- Route Name: api.v1.tenant.realestate.search.global
- Controller: Modules/RealEstate/Http/Controllers/FrontendSearchController.php
- Service: Modules/RealEstate/Services/SearchService.php
- Resource: Modules/RealEstate/Transformers/UnifiedSearchResultResource.php

### Dependencies
SearchService, UnifiedSearchResultResource, FrontendSearchController

### Features
- Unified multi-entity search (Properties, Compounds, Areas, Developers)
- 13+ filter parameters (q, purpose, property_type_id, min_price, max_price, bedrooms, bathrooms, area_id, developer_id, amenities, finishing, entity_type, per_page, page, sort)
- Relevance-based scoring (Exact match: 100, Starts with: 80, Contains: 60, Description: 40, Bonuses for featured/recent/media)
- Redis/Memcached tag-based caching with 5-minute TTL
- Standardized response format with counts_by_type metadata
