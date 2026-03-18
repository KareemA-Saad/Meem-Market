## Relations
@real_estate/architecture/real_estate_module_architecture.md
@real_estate/api_implementation/phase_2_controllers_and_api_endpoints.md

## Raw Concept
**Task:**
Phase 6 RealEstate Geo-spatial Features

**Changes:**
- Added latitude, longitude, and address columns to real estate tables.
- Implemented SPATIAL indexes for geo-spatial query performance.
- Developed SearchService methods for Haversine distance, bounding box search, and zoom-level clustering.
- Added frontend endpoints for map-based property discovery and clustering.

**Files:**
- Modules/RealEstate/Services/SearchService.php
- Modules/RealEstate/Http/Controllers/Frontend/SearchController.php
- Modules/RealEstate/Database/Migrations/2024_01_20_000001_add_geospatial_columns_to_re_properties_table.php
- Modules/RealEstate/Database/Migrations/2024_01_20_000002_add_geospatial_columns_to_re_compounds_table.php

**Flow:**
Map Viewport Change -> Frontend requests /map/clusters or /map/properties -> SearchController validates bounds/zoom -> SearchService executes SPATIAL query or Haversine filter -> Results returned with cluster stats or property details.

**Timestamp:** 2026-01-20

## Narrative
### Structure
# Geo-spatial Structure
- **Database Schema**:
  - `re_properties` & `re_compounds`: `latitude` (decimal 10,7), `longitude` (decimal 10,7), `address` (varchar 500).
- **Core Methods (SearchService)**:
  - `getNearbyProperties(lat, lng, radius, limit)`: Haversine-based radius search.
  - `searchPropertiesInBounds(neLat, neLng, swLat, swLng, filters)`: Bounding box search.
  - `getPropertyClusters(zoom, bounds)`: Precision-based clustering.
  - `calculateDistance(lat1, lon1, lat2, lon2)`: Haversine distance helper.
- **Endpoints**:
  - `GET /api/v1/tenant/{tenant}/realestate/search/nearby`: Radius-based discovery.
  - `GET /api/v1/tenant/{tenant}/realestate/map/properties`: Viewport property listing.
  - `GET /api/v1/tenant/{tenant}/realestate/map/clusters`: Clustered map markers with stats.

### Dependencies
# Geo-spatial Dependencies
- **Database**: MySQL with SPATIAL indexes (`re_properties_spatial_idx`, `re_properties_geo_idx`).
- **Middleware**: `tenancy.token` and `tenant.context` for tenant isolation.
- **Service**: `SearchService` (Modules/RealEstate/Services/SearchService.php).
- **Controller**: `SearchController` (Modules/RealEstate/Http/Controllers/Frontend/SearchController.php).

### Features
# Geo-spatial Features
- **Nearby Search**: Find properties within a specific radius using the Haversine formula.
- **Viewport Search**: Retrieve properties visible within a map's bounding box (NorthEast/SouthWest coordinates).
- **Property Clustering**: Dynamic clustering of property markers based on map zoom levels:
  - Zoom 1-5: Country level.
  - Zoom 6-10: City level.
  - Zoom 11-15: Neighborhood level.
  - Zoom 16+: Street/Individual level.
- **Cluster Stats**: Clusters return aggregated data including property counts and price statistics (min, max, avg).
- **Area Proximity**: Find properties near the center of a defined Area entity.
