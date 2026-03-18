## Raw Concept
**Task:**
Enable manual subscription bypass for local testing.

**Changes:**
- Added bypass logic in `completeSubscription` to handle 'manual' gateway or 'local' environment.
- Auto-generation of transaction ID for bypassed payments.

**Files:**
- app/Services/SubscriptionService.php

**Flow:**
completeSubscription() -> Check if gateway is 'manual' or env is 'local' -> If true, generate manual transaction ID if missing -> Proceed to complete subscription and tenant creation/update.

**Timestamp:** 2026-01-11

## Narrative
### Structure
- app/Services/SubscriptionService.php: completeSubscription() method

### Dependencies
- App\Services\SubscriptionService
- config('app.env')
- PaymentLog model

### Features
# Manual Subscription Bypass
- Allows testing subscription flows without real payment gateway integration.
- Triggered when `paymentGateway` is 'manual' or `APP_ENV` is 'local'.
- Automatically generates a `transaction_id` (prefixed with 'manual_') if none is provided.
