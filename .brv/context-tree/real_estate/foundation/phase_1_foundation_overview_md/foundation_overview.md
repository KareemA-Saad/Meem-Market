## Relations
@real_estate/foundation/phase_1_foundation_overview.md
@real_estate/architecture/domain_models.md

## Raw Concept
**Task:**
Configure RealEstate module lookup data and realistic simulation seeders.

**Changes:**
- Expanded lookup data seeding for amenities and property types.
- Implemented realistic media and lead simulation for development testing.
- Added status transition timelines for simulated inquiries.

**Files:**
- Modules/RealEstate/Database/Seeders/*.php

**Flow:**
Base Seeders -> (Amenity/Type) -> Dependent Seeders -> (Image/Inquiry)

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Database/Seeders/AmenitySeeder.php
Modules/RealEstate/Database/Seeders/PropertyTypeSeeder.php
Modules/RealEstate/Database/Seeders/PropertyImageSeeder.php
Modules/RealEstate/Database/Seeders/PropertyInquirySeeder.php

### Dependencies
- Laravel Seeder system
- RealEstate Entities (Amenity, PropertyType, PropertyImage, CompoundImage, PropertyInquiry)
- Multi-language support (English/Arabic)
- Carbon for timestamp generation

### Features
- Amenity Seeding: Establishes a comprehensive list of amenities for both compounds (e.g., Gym, Security) and properties (e.g., Air Conditioning, Balcony) with icons and ordering.
- Property Types: Defines core real estate categories like Apartment, Villa, Townhouse, and Commercial types (Office, Shop).
- Media Simulation: PropertyImageSeeder generates sample image paths for compounds and properties, covering various categories (living room, bedroom, etc.) and setting primary/thumbnail flags.
- Lead Simulation: PropertyInquirySeeder creates realistic lead data with varied statuses (New, Contacted, Qualified, etc.), source tracking (Website, Mobile App), and admin notes.
- Lifecycle Timeline: Generated inquiries include realistic timelines for status transitions (contacted_at, qualified_at, etc.).
- Count Synchronization: Automatically updates inquiry counts on related property models after seeding.
