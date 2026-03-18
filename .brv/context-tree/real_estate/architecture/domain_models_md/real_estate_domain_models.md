## Relations
@real_estate/architecture/property_service_implementation.md
@real_estate/architecture/real_estate_module_configuration.md

## Raw Concept
**Task:**
Define RealEstate domain models and relationships.

**Changes:**
- Established core domain models for RealEstate module.
- Implemented hierarchical geographical structure for Areas.
- Added CRM-lite capabilities for PropertyInquiries.
- Integrated SEO and translation support across all models.

**Files:**
- Modules/RealEstate/Entities/Property.php
- Modules/RealEstate/Entities/Compound.php
- Modules/RealEstate/Entities/Area.php
- Modules/RealEstate/Entities/Developer.php
- Modules/RealEstate/Entities/PropertyInquiry.php

**Flow:**
Developer -> Compound -> Property -> Inquiry / SavedProperty

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Entities/Property.php
Modules/RealEstate/Entities/Compound.php
Modules/RealEstate/Entities/Area.php
Modules/RealEstate/Entities/Developer.php
Modules/RealEstate/Entities/PropertyInquiry.php

### Dependencies
- Spatie Translatable (HasTranslations)
- Laravel MetaInfo (morphOne)
- Laravel User model (BelongsTo, BelongsToMany)
- Relation to Compound, Area, Developer, PropertyType, Amenity, Image, Inquiry models

### Features
- Hierarchical Area management (Parent/Child/Descendants) with type enums.
- Developer entity with contact info and established year tracking.
- Project-based Compound model with geo-spatial coordinates and price range caching.
- Unit-based Property model with pricing (sale/rent), features (beds/baths/area), and availability tracking.
- Lead-based Inquiry model with CRM lifecycle (new -> contacted -> qualified -> converted -> closed) and source tracking.
- Translatable SEO fields (Meta Title/Description) for all core entities.
- Cached statistics (counts/views) for performance optimization.
