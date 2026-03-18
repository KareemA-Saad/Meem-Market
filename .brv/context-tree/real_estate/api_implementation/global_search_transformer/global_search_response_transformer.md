## Relations
@real_estate/api_implementation/global_search_feature/global_search_feature_implementation.md
@real_estate/api_implementation/api_resources_and_transformers_md/real_estate_api_resources_and_transformers.md

## Raw Concept
**Task:**
Implement unified response transformation for Global Search results

**Changes:**
- Added UnifiedSearchResultResource class with multi-entity transformation logic
- Implemented transformProperty, transformCompound, transformArea, transformDeveloper helpers
- Added formatPriceRange utility for Compound entities
- Integrated OpenAPI attributes for schema documentation

**Files:**
- Modules/RealEstate/Transformers/UnifiedSearchResultResource.php

**Flow:**
Entity Collection -> UnifiedSearchResultResource::collection() -> toArray() -> detect entity_type -> transformSpecificEntity() -> Unified JSON Output

**Timestamp:** 2026-02-10

## Narrative
### Structure
Modules/RealEstate/Transformers/UnifiedSearchResultResource.php

### Dependencies
Request, JsonResource, OpenApi\Attributes as OA

### Features
- Unified transformation for Property, Compound, Area, and Developer entities
- Includes common fields: type, id, title, description, url, image, relevance_score
- Maps 'listing_type' to 'purpose' for Properties
- Formats price ranges for Compounds
- Provides entity-specific metadata in a nested 'data' object
- Integrated OpenAPI schema documentation (oneOf: UnifiedSearchResult_Property, Compound, etc.)
