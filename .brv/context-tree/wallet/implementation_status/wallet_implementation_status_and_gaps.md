## Relations
@compliance/validation/phase_7_status.md

## Raw Concept
**Task:**
Implement critical fixes for Wallet module race conditions and manual workflows.

**Changes:**
- Implemented lockForUpdate() for concurrency safety.
- Added idempotency checks for transaction integrity.
- Added manual payment deposit and approval workflow.
- Implemented negative balance prevention.
- Added comprehensive error and operation logging.

**Files:**
- Modules/Wallet/Services/WalletService.php
- Modules/Wallet/Services/WalletHistoryService.php
- Modules/Wallet/Http/Controllers/Api/V1/Admin/WalletHistoryController.php
- Modules/Wallet/Http/Controllers/Api/V1/Frontend/WalletController.php
- Modules/Wallet/Http/Requests/DepositWalletRequest.php
- Modules/Wallet/Http/Requests/ApproveManualPaymentRequest.php
- Modules/Wallet/Routes/api.php

**Flow:**
User Deposit -> WalletHistory (Pending) -> Admin Approval (Transaction + Lock) -> Balance Update -> History (Complete)

**Timestamp:** 2026-01-14

## Narrative
### Structure
- Modules/Wallet/Services/WalletService.php\n- Modules/Wallet/Http/Controllers/Api/V1/Admin/WalletHistoryController.php\n- Modules/Wallet/Http/Controllers/Api/V1/Frontend/WalletController.php

### Dependencies
Requires DB transactions and pessimistic locking.

### Features
- Pessimistic Locking: Uses `lockForUpdate()` in `addFunds()` and `deductFunds()` to prevent race conditions during balance updates.\n- Idempotency: Prevents duplicate transactions via `getHistoryByTransactionId()` check.\n- Manual Payment Workflow: User deposit submission (with image) and admin approval system.\n- Negative Balance Prevention: Strict validation in `deductFunds()`.\n- Observability: Comprehensive logging for all balance changes, errors, and admin actions.
