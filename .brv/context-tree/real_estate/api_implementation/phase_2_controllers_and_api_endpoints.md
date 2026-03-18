## Relations
@real_estate/foundation/phase_1_foundation_overview.md
@code_style/backend_standards/backend_architecture_and_coding_standards.md

## Raw Concept
**Task:**
RealEstate Module Phase 2 Implementation - Controllers & API Endpoints

**Changes:**
- Added 11 API Resource classes for data transformation.
- Added 7 Admin Controllers for backend management.
- Added 9 Frontend Controllers for public API access.
- Defined 3-tier route structure in api.php.

**Files:**
- Modules/RealEstate/Routes/api.php
- Modules/RealEstate/Http/Controllers/Frontend/SearchController.php
- Modules/RealEstate/Http/Controllers/Admin/PropertyController.php
- Modules/RealEstate/Transformers/PropertyResource.php

**Flow:**
Request -> Route (Tier 1/2/3) -> Controller (Admin/Frontend) -> Service -> Resource -> Response

**Timestamp:** 2026-01-20

## Narrative
### Structure
Modules/RealEstate/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── Frontend/
│   └── Resources/ (Transformers)
└── Routes/
    └── api.php

### Dependencies
- Modules/RealEstate
- Spatie QueryBuilder
- OpenAPI attributes
- Cache tagging
- Sanctum for authentication

### Features
### API Resource Classes (11 Transformers)
- PropertyResource/PropertyCollection: Full property transformation with relations, Nawy-style URL helper.
- CompoundResource/CompoundCollection: Compound with properties_count.
- AreaResource: Hierarchical area with counts.
- DeveloperResource: Developer with compounds/properties counts.
- PropertyTypeResource, AmenityResource, PropertyImageResource, CompoundImageResource.
- PropertyInquiryResource: CRM inquiry with conditional admin fields.

### Admin Controllers (7 files)
- PropertyController: CRUD, bulk actions, statistics, image management (upload/delete/reorder/setPrimary).
- CompoundController: CRUD, bulk, statistics, updatePrices, image management.
- AreaController: CRUD, tree, reorder, children, statistics.
- DeveloperController, PropertyTypeController, AmenityController: Standard CRUD and specialized helpers.
- PropertyInquiryController: CRM workflow, status transitions, agent assignment, bulk operations, export.

### Frontend Controllers (9 files)
- PropertyController: List, show (id-slug pattern), featured, similar.
- CompoundController: List, show, featured, compound properties.
- AreaController: Tree, cities, featured, children, compounds, properties, breadcrumbs.
- DeveloperController, PropertyTypeController, AmenityController: Public-facing lookups.
- PropertyInquiryController: Submit inquiries for property/compound/general.
- SearchController: Advanced search, autocomplete, facets, popular, nearby (geo-search).
- SavedPropertyController: User favorites (index, store, destroy, toggle, check).

### Route Structure (3 Tiers)
1. Public Routes: `/realestate/*` - No auth required.
2. Authenticated Routes: `/realestate/saved-properties/*` - `auth:sanctum` middleware.
3. Admin Routes: `/admin/realestate/*` - `auth:sanctum` + `package.active` + `feature:realestate`.
