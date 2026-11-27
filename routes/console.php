<?php

use App\View\GuestHomeCache;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:key:ensure', function (): int {
    if (filled(config('app.key'))) {
        $this->info('Application key already configured.');

        return 0;
    }

    return $this->call('key:generate', ['--force' => true]);
})->purpose('Generate an application key only when one is missing');

Artisan::command('homepage:warm', function (GuestHomeCache $cache): void {
    $cache->warm();
    $this->info('Guest homepage cache warmed.');
})->purpose('Pre-render the latest recipes on the guest homepage');

Schedule::command('homepage:warm')->everyFiveMinutes()->withoutOverlapping();
