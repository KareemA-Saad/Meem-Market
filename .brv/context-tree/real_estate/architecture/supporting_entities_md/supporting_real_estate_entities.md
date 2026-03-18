## Relations
@real_estate/architecture/domain_models.md

## Raw Concept
**Task:**
Define supporting entities and media management for RealEstate.

**Changes:**
- Added supporting entities for media, amenities, and property types.
- Implemented categorized amenity system.
- Standardized image management with ordering and primary flags.

**Files:**
- Modules/RealEstate/Entities/Amenity.php
- Modules/RealEstate/Entities/PropertyType.php
- Modules/RealEstate/Entities/PropertyImage.php
- Modules/RealEstate/Entities/CompoundImage.php

**Flow:**
Property/Compound -> Image / Amenity / PropertyType

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Entities/Amenity.php
Modules/RealEstate/Entities/PropertyType.php
Modules/RealEstate/Entities/PropertyImage.php
Modules/RealEstate/Entities/CompoundImage.php

### Dependencies
- Pivot tables for many-to-many relations (amenities, saved properties)
- Image models for media management with ordering
- PropertyType for categorization

### Features
- Amenity: Categorized by compound, property, or both.
- PropertyType: Defines unit types (Villa, Apartment, etc.) with custom ordering.
- Image Models: Handles file paths, primary flags, and derived thumbnail logic for both Properties and Compounds.
- Pivot Tables: re_property_amenities, re_compound_amenities, re_saved_properties.
