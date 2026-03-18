## Relations
@real_estate/api_implementation/phase_2_controllers_and_api_endpoints.md
@real_estate/architecture/property_service_implementation.md

## Raw Concept
**Task:**
Define and enforce RealEstate module validation rules.

**Changes:**
- Standardized request validation across all RealEstate entities.
- Implemented strict enum validation for statuses and types.
- Added geo-coordinate and pricing boundary checks.
- Integrated OpenApi documentation within request classes.

**Files:**
- Modules/RealEstate/Http/Requests/*.php

**Flow:**
Request -> FormRequest Validation -> Controller Action

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Http/Requests/StorePropertyRequest.php
Modules/RealEstate/Http/Requests/UpdatePropertyRequest.php
Modules/RealEstate/Http/Requests/StoreCompoundRequest.php
Modules/RealEstate/Http/Requests/StoreAreaRequest.php
Modules/RealEstate/Http/Requests/UpdatePropertyInquiryRequest.php

### Dependencies
- Laravel FormRequest
- OpenApi Attributes (Swagger)
- Custom validation rules (exists, Rule::in, unique)
- Relation to Property, Compound, Area, Developer, Amenity, Inquiry models

### Features
- Property: Validates compound/type existence, pricing (min 0), listing types (sale/rent), payment options, features (beds/baths), and SEO fields.
- Compound: Validates area/developer existence, geo-coordinates (lat -90 to 90, long -180 to 180), construction status, and unit counts.
- Area: Validates parent hierarchy, type enums, and SEO metadata.
- Inquiry: Restricts status changes to defined CRM lifecycle (new, contacted, qualified, converted, closed) and validates agent assignment.
- Bulk Actions: (Inferred from PropertyService) Validates action types like delete, publish, and feature.
- Slug Generation: Ensures unique slugs for properties, compounds, and areas with ignore rules for updates.
