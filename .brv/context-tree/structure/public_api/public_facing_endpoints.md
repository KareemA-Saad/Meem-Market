## Raw Concept
**Task:**
Manage public-facing storefront API endpoints including new search functionality.

**Changes:**
- Added /api/v1/search endpoint for global content discovery.
- Updated HomeController to include dynamic section loading.
- Exposed endpoints for home, countries, branches, offers, about, careers, and contact.
- Implemented public settings retrieval by group.

**Files:**
- app/Http/Controllers/Api/V1/HomeController.php
- app/Http/Controllers/Api/V1/BranchController.php
- app/Http/Controllers/Api/V1/OfferController.php
- app/Http/Controllers/Api/V1/SettingController.php

**Flow:**
GET /api/v1/search?q=... -> SearchController -> Query Posts/Pages/Terms -> UnifiedSearchResultResource

**Timestamp:** 2026-02-18

## Narrative
### Structure
Public routes are located under the /api/v1/ prefix and remain unauthenticated. They interact with storefront-specific models like Slider, Branch, and Offer.

### Features
Public settings are retrieved by group (e.g., /api/v1/settings/general). New global search endpoint allows querying across multiple content types with relevance scoring.

### Rules
Public endpoints must remain untouched by admin layer changes. Search results must respect post_status="publish" and privacy settings.
