## Relations
@tenancy/architecture/architecture_overview.md
@real_estate/foundation/phase_1_foundation_overview.md
@real_estate/api_implementation/phase_2_controllers_and_api_endpoints.md

## Raw Concept
**Task:**
RealEstate Module Architecture Definition

**Changes:**
- Defined the high-level architecture for the RealEstate module within a SaaS multi-tenant environment.
- Established routing patterns for Public, Admin, and Agent personas.
- Defined the core entity model and service layer structure.
- Specified database prefix and SEO-friendly URL patterns.

**Files:**
- Modules/RealEstate/Routes/api.php
- Modules/RealEstate/Entities/*.php
- Modules/RealEstate/Services/*.php
- Modules/RealEstate/Http/Controllers/*.php

**Flow:**
Tenant Request -> Tenancy Middleware (Isolation) -> Feature Middleware (Subscription Check) -> Persona Middleware (Auth) -> Controller -> Service Layer -> Geo-spatial/Database -> Transformer -> Tenant-specific Response.

**Timestamp:** 2026-01-20

## Narrative
### Structure
# Architecture Structure
- **Namespace**: `Modules\RealEstate`
- **Database**: All tables use the `re_` prefix.
- **Routing**:
  - Public: `/api/v1/tenant/{tenant}/realestate/*`
  - Admin: `/api/v1/tenant/{tenant}/admin/realestate/*`
  - Agent: `/api/v1/tenant/{tenant}/agent/realestate/*`
- **Core Models**:
  - `Property`, `Compound`, `Area`, `Developer`, `PropertyType`, `Amenity`.
  - `PropertyImage`, `CompoundImage`, `PropertyInquiry`, `SavedProperty`.
- **Core Services**:
  - `PropertyService`, `CompoundService`, `AreaService`, `InquiryService`, `SearchService`.
- **Controllers**:
  - 10 Frontend (Public Listings)
  - 8 Admin (CRUD)
  - 1 Agent Dashboard

### Dependencies
# Architectural Dependencies
- **Tenancy System**: Uses `tenancy.token` and `tenant.context` middleware for data isolation.
- **Subscription Enforcement**: Controlled via `package.active` and `feature:realestate` middleware.
- **Authentication**: `auth:api_tenant_admin` for administrative routes.
- **Geo-spatial**: `SearchService` includes geo-spatial support for nearby searches.
- **Image Processing**: `intervention/image` for multi-size thumbnails.

### Features
# Real Estate Module Features
- **Tenant Isolation**: Each tenant has isolated properties, compounds, and inquiries based on their subscription plan.
- **Advanced Search**: Faceted search and geo-spatial nearby search capabilities.
- **Multi-image Gallery**: Automated thumbnail generation (small, medium, large).
- **Lead Management/CRM**: Inquiry system for tracking customer interest.
- **Saved Properties**: User-specific saved listings.
- **URL Pattern**: Nawy-style `{id}-{slug}` URLs for SEO-friendly frontend routes.
