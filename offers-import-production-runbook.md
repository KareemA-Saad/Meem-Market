# Offers Import Production Runbook

## 1) Required Environment

Set these values in production `.env`:

```dotenv
APP_URL=https://your-domain.com

OFFERS_IMPORT_ENABLED=true
OFFERS_IMPORT_CRON="0 3 * * *"
OFFERS_IMPORT_SOURCE=public/images/offers
OFFERS_IMPORT_CATEGORY_SLUG=coming-winter-offers
OFFERS_IMPORT_NAME_PREFIX=offer
OFFERS_IMPORT_START_ORDER=1
OFFERS_IMPORT_UPLOADER=admin@meemmark.com
OFFERS_IMPORT_REPLACE=true
OFFERS_IMPORT_ALLOW_LOCAL_APP_URL=false
```

Then run:

```bash
php artisan config:clear
php artisan config:cache
```

## 2) First Manual Production Run

Run once manually to validate:

```bash
php artisan offers:import-images --replace
```

If `APP_URL` is still localhost, command will fail by design.

## 3) Scheduler Automation

The schedule is already wired in `routes/console.php` and reads the `OFFERS_IMPORT_*` env vars.

Server cron (Linux) should run Laravel scheduler every minute:

```cron
* * * * * cd /var/www/your-app && php artisan schedule:run >> /dev/null 2>&1
```

## 4) Logs and Monitoring

- Scheduled command output is appended to:
  - `storage/logs/offers-import.log`
- Application errors remain in:
  - `storage/logs/laravel.log`

## 5) Safe Operations

- Use `--dry-run` before changes:
  - `php artisan offers:import-images --dry-run`
- `--replace` deactivates existing offers in target category that are not in current import batch.
- Re-running is idempotent for already imported `offer-*` entries.
