## Relations
@real_estate/api_documentation/phase_3_swagger_documentation.md
@real_estate/api_implementation/api_resources_and_transformers.md

## Raw Concept
**Task:**
Document RealEstate module API schemas and tags.

**Changes:**
- Consolidated RealEstate OpenAPI/Swagger documentation into a central container.
- Defined reusable response and entity schemas for API consistency.
- Standardized search facets and statistics output documentation.

**Files:**
- Modules/RealEstate/Http/Swagger/RealEstateSwaggerInfo.php

**Flow:**
OpenAPI Attributes -> Swagger UI / Client SDK Generation

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Http/Swagger/RealEstateSwaggerInfo.php

### Dependencies
- OpenApi Attributes (Swagger)
- Integration with RealEstate Controllers and Transformers

### Features
- Tag Organization: Defines 15+ tags to categorize public, user, and admin endpoints (Properties, Compounds, Search, Admin - Inquiries, etc.).
- Reusable Schemas: Provides standardized schemas for pagination meta, price ranges, area sizes, and geo-locations.
- Response Standardization: Establishes success, error, and validation error response formats used throughout the module.
- Entity Schemas: Defines detailed structures for Property (List/Detail), Compound (List), and Area resources.
- Search & Discovery: Includes schemas for search filters, autocomplete suggestions, and faceted search results (counts by type/area/beds).
- Lead Management: Outlines inquiry submission and response schemas, including expected response times.
- Analytics: Defines comprehensive statistics schemas for both properties (by type/area) and inquiries (by status/source/timeline).
