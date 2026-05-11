<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Support\OperationsAlertService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('operations:scan-alerts', function (OperationsAlertService $alerts) {
    $items = $alerts->alerts();

    if ($items->isEmpty()) {
        $this->info('No SLA alerts right now.');

        return;
    }

    foreach ($items as $alert) {
        $this->line(sprintf('%s: %s', $alert['label'], $alert['badge']));
    }
})->purpose('Scan overdue workflow items and print current alert counts.');

Schedule::command('operations:scan-alerts')->hourly();
