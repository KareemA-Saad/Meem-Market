<?php

use App\Console\Commands\ImportOfferImagesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('offers_import.enabled')) {
    $parameters = [
        '--source' => (string) config('offers_import.source'),
        '--category-slug' => (string) config('offers_import.category_slug'),
        '--name-prefix' => (string) config('offers_import.name_prefix'),
        '--start-order' => (int) config('offers_import.start_order'),
    ];

    $uploader = trim((string) config('offers_import.uploader', ''));
    if ($uploader !== '') {
        $parameters['--uploader'] = $uploader;
    }

    if ((bool) config('offers_import.replace', true)) {
        $parameters['--replace'] = true;
    }

    if ((bool) config('offers_import.allow_local_app_url', false)) {
        $parameters['--allow-local-app-url'] = true;
    }

    Schedule::command(ImportOfferImagesCommand::class, $parameters)
        ->name('offers-import-images')
        ->cron((string) config('offers_import.cron', '0 3 * * *'))
        ->withoutOverlapping()
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/offers-import.log'));
}
