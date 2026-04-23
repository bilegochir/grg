<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseRequirement;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'client' => Client::class,
            'user' => User::class,
            'visa_case' => VisaCase::class,
            'visa_case_requirement' => VisaCaseRequirement::class,
        ]);
    }
}
