## Relations
@tenancy/architecture/architecture_overview.md
@tenancy/tenant_service/tenant_service_implementation.md

## Raw Concept
**Task:**
Document subscription initiation and tenant provisioning flow.

**Changes:**
- Implemented dual-path subscription initiation logic.
- Integrated auto-completion for free/trial plans.
- Linked tenant creation to automatic database provisioning via events/jobs.

**Files:**
- app/Http/Controllers/Api/V1/Landlord/SubscriptionController.php
- app/Services/SubscriptionService.php

**Flow:**
initiate() -> initiateSubscription() (creates PaymentLog) -> If Free/Trial: completeSubscription() -> createTenant() -> TenantCreated Event -> CreateTenantDatabase Job -> Return Success. Else: Return Pending PaymentLog + Gateways.

**Timestamp:** 2026-01-11

## Narrative
### Structure
- app/Http/Controllers/Api/V1/Landlord/SubscriptionController.php: initiate()
- app/Services/SubscriptionService.php: initiateSubscription(), completeSubscription(), createTenant()

### Dependencies
- App\Http\Controllers\Api\V1\Landlord\SubscriptionController
- App\Services\SubscriptionService
- Stancl\Tenancy\Events\TenantCreated
- App\Jobs\CreateTenantDatabase
- QUEUE_CONNECTION (sync/async)

### Features
# Subscription Initiation Flow
- Handles both paid and free/trial subscription paths.
- **Free/Trial Path**: Auto-completes initiation, creates Tenant, and triggers immediate DB setup if queue is sync.
- **Paid Path**: Leaves PaymentLog in 'pending' status, returns available gateways for frontend payment processing.
- **Tenant Creation**: Standard Stancl `Tenant::create()` triggers the `TenantCreated` event, which leads to `CreateTenantDatabase`.
