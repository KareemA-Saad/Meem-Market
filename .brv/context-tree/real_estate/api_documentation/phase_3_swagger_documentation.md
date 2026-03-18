## Relations
@real_estate/api_implementation/phase_2_controllers_and_api_endpoints.md
@compliance/validation/phase_7_business_logic_validation_status.md

## Raw Concept
**Task:**
RealEstate Module Phase 3 - Swagger Documentation

**Changes:**
- Created RealEstateSwaggerInfo.php for centralized OpenAPI definitions.
- Updated config/l5-swagger.php to include RealEstate module paths.
- Implemented 'RE_' prefix naming convention for all module schemas.
- Annotated 13 FormRequests and 11 Transformers with OpenAPI attributes.

**Files:**
- Modules/RealEstate/Http/Swagger/RealEstateSwaggerInfo.php
- config/l5-swagger.php
- Modules/RealEstate/Transformers/PropertyResource.php

**Flow:**
Annotations in Controllers/Requests/Resources -> Centralized Schemas in SwaggerInfo -> L5-Swagger Generate -> Swagger UI

**Timestamp:** 2026-01-20

## Narrative
### Structure
Modules/RealEstate/
└── Http/
    └── Swagger/
        └── RealEstateSwaggerInfo.php (Centralized Definitions)
config/
└── l5-swagger.php (Path Configuration)

### Dependencies
- L5-Swagger (OpenAPI 3.0)
- Modules/RealEstate/Http/Swagger/RealEstateSwaggerInfo.php
- config/l5-swagger.php

### Features
### Centralized Swagger Definition
- `RealEstateSwaggerInfo.php`: Serves as the central container for all RealEstate-related OpenAPI schemas and tags.

### Schema Naming Convention
- **Prefix:** `RE_` (e.g., `RE_PropertyResource`, `RE_SearchFilters`).
- **Purpose:** Avoids naming collisions with other modules (like HotelBooking).

### Key Schema Categories
- **Common:** `RE_PaginationMeta`, `RE_PriceRange`, `RE_AreaSize`, `RE_GeoLocation`.
- **Responses:** `RE_SuccessResponse`, `RE_ErrorResponse`, `RE_ValidationErrorResponse`.
- **Public entities:** `RE_PropertyListItem`, `RE_PropertyDetail`, `RE_CompoundListItem`.
- **Search:** `RE_SearchFilters`, `RE_AutocompleteSuggestion`, `RE_SearchFacets`.
- **CRM/Inquiries:** `RE_InquirySubmission`, `RE_InquiryResponse`.
- **Admin Stats:** `RE_PropertyStatistics`, `RE_InquiryStatistics`.

### Integration Points
- **FormRequests:** 13 files updated with `RE_` prefixed schemas for validation documentation.
- **Transformers:** 11 files (e.g., `PropertyResource`) updated with schema attributes.
- **Controllers:** Both Admin and Frontend controllers reference these centralized schemas.

### Generation & Access
- **Command:** `php artisan l5-swagger:generate`
- **URL:** `/api/documentation`
