## Relations
@real_estate/architecture/domain_models.md
@tenancy/architecture/architecture_overview.md

## Raw Concept
**Task:**
Define RealEstate database schema and optimization.

**Changes:**
- Established normalized schema for RealEstate module.
- Implemented JSON columns for flexible data (installments, floor plans).
- Added decimal precision for pricing and geo-coordinates.
- Optimized database with composite indexes for search and filtering.

**Files:**
- Modules/RealEstate/Database/Migrations/*.php

**Flow:**
Schema Definition -> Migration Execution (Tenant DB)

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Database/Migrations/2024_01_19_000001_create_re_areas_table.php
Modules/RealEstate/Database/Migrations/2024_01_19_000002_create_re_developers_table.php
Modules/RealEstate/Database/Migrations/2024_01_19_000005_create_re_compounds_table.php
Modules/RealEstate/Database/Migrations/2024_01_19_000006_create_re_properties_table.php
Modules/RealEstate/Database/Migrations/2024_01_19_000011_create_re_property_inquiries_table.php

### Dependencies
- Tenant-only migrations
- MySQL/PostgreSQL support (JSON/Decimal types)

### Features
- re_areas: Hierarchical structure with type enums and SEO fields.
- re_developers: Profile info and cached property counts.
- re_compounds: Project details, geo-coordinates, and price range caching.
- re_properties: Unit details, pricing models (sale/rent), and installment JSON.
- re_property_inquiries: CRM tracking with status enums and source metadata.
- Media/Pivot tables: Normalized storage for images and many-to-many features.
- Performance: Extensive indexing on filtering and status columns.
