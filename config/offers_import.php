<?php

return [
    'enabled' => env('OFFERS_IMPORT_ENABLED', false),
    'cron' => env('OFFERS_IMPORT_CRON', '0 3 * * *'),
    'source' => env('OFFERS_IMPORT_SOURCE', 'public/images/offers'),
    'category_slug' => env('OFFERS_IMPORT_CATEGORY_SLUG', 'coming-winter-offers'),
    'name_prefix' => env('OFFERS_IMPORT_NAME_PREFIX', 'offer'),
    'start_order' => (int) env('OFFERS_IMPORT_START_ORDER', 1),
    'uploader' => env('OFFERS_IMPORT_UPLOADER', 'admin@meemmark.com'),
    'replace' => env('OFFERS_IMPORT_REPLACE', true),
    'allow_local_app_url' => env('OFFERS_IMPORT_ALLOW_LOCAL_APP_URL', false),
];
