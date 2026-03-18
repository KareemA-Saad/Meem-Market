## Relations
@real_estate/api_implementation/phase_2_controllers_and_api_endpoints.md
@real_estate/lead_management/phase_4_lead_management_notifications.md

## Raw Concept
**Task:**
Phase 5 RealEstate Media Management

**Changes:**
- Implemented multi-image upload with Intervention Image integration.
- Created AdminMediaController for backend gallery management.
- Created Frontend GalleryController for public image access.
- Added automatic thumbnail generation (3 sizes) for all property/compound uploads.
- Implemented image reordering and primary image selection logic.

**Files:**
- Modules/RealEstate/Http/Controllers/Admin/MediaController.php
- Modules/RealEstate/Http/Controllers/Frontend/GalleryController.php
- Modules/RealEstate/Entities/PropertyImage.php
- Modules/RealEstate/Entities/CompoundImage.php
- Modules/RealEstate/Transformers/PropertyImageResource.php
- Modules/RealEstate/Transformers/CompoundImageResource.php

**Flow:**
Admin uploads images -> MediaController stores original -> generateThumbnails() creates 3 versions in /thumbs/ directory -> Database records order and primary status -> Frontend GalleryController retrieves via Resources -> Resources generate absolute URLs for all sizes.

**Timestamp:** 2026-01-20

## Narrative
### Structure
# Media Management Structure
- **Controllers**:
  - `Modules/RealEstate/Http/Controllers/Admin/MediaController.php`: Admin operations (upload, reorder, delete).
  - `Modules/RealEstate/Http/Controllers/Frontend/GalleryController.php`: Public gallery retrieval.
- **Transformers**:
  - `Modules/RealEstate/Transformers/PropertyImageResource.php`
  - `Modules/RealEstate/Transformers/CompoundImageResource.php`
- **Endpoints**:
  - `POST /api/admin/realestate/properties/{id}/images`: Upload multi-images.
  - `PUT /api/admin/realestate/properties/{id}/images/reorder`: Update display order.
  - `PUT /api/admin/realestate/properties/{id}/images/{image}/primary`: Set main image.
  - `GET /api/realestate/properties/{id}/gallery`: Fetch property images.
  - `GET /api/realestate/compounds/{id}/gallery`: Fetch compound images (optional type filter).

### Dependencies
# Media Dependencies
- `intervention/image`: Used for thumbnail generation.
- `Storage`: Local/Public disk for image persistence.
- `PropertyImageResource` & `CompoundImageResource`: API transformers for URL generation.

### Features
# Real Estate Media Management
- **Multi-image Upload**: Support for batch uploading images for Properties and Compounds.
- **Thumbnail Generation**: Automatic creation of three sizes for every upload:
  - `small`: 300x200
  - `medium`: 600x400
  - `large`: 1200x800
- **Gallery Management**:
  - Reordering (drag-and-drop support via `order` field).
  - Primary image selection (updates entity thumbnail).
  - Categorization for Compounds (gallery, master_plan, unit_plan).
- **Public Access**: Dedicated frontend gallery endpoints with optimized thumbnail URLs.
