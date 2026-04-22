<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('crm:about', function () {
    $this->info('VisaFlow CRM console is ready.');
    $this->line('Use php artisan migrate and composer run dev to work locally.');
})->purpose('Display the VisaFlow CRM console status.');
