## Relations
@real_estate/api_implementation/phase_2_controllers_and_api_endpoints.md
@real_estate/architecture/domain_models.md

## Raw Concept
**Task:**
Normalize RealEstate API output and implement calculated fields.

**Changes:**
- Standardized API response structure for all RealEstate entities.
- Implemented calculated field logic (price per sqm, formatted prices) in transformers.
- Added conditional attribute loading for admin-specific data.
- Standardized media output with thumbnail support.

**Files:**
- Modules/RealEstate/Transformers/*.php

**Flow:**
Eloquent Model -> Transformer -> JSON Response

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Transformers/PropertyResource.php
Modules/RealEstate/Transformers/CompoundResource.php
Modules/RealEstate/Transformers/AreaResource.php
Modules/RealEstate/Transformers/PropertyInquiryResource.php
Modules/RealEstate/Transformers/PropertyImageResource.php

### Dependencies
- Laravel JsonResource
- OpenApi Attributes (Swagger)
- Storage Facade for URL generation
- Relation to Property, Compound, Area, Developer, Amenity, Image, Inquiry models

### Features
- Property: Normalizes output with calculated fields (price_formatted, price_per_meter), SEO-friendly id_slug, and embedded relations (area, compound, developer, images, amenities).
- Compound: Includes price range caching results, total units, and hierarchical area data.
- Area: Supports recursive tree structures (parent/children) and includes property/compound counts.
- Inquiry: Differentiates between public and admin views (hiding admin_notes/IP/User Agent from non-admins) and includes assigned agent/user details.
- Media: Standardizes image output with multiple thumbnail sizes (small, medium, large) and primary image flags.
- Formatting: Standardizes currency symbols, date-time formats (ISO 8601), and measurement units.
