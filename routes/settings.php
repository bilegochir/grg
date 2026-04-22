<?php

use App\Http\Controllers\Settings\AgencyController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TaskStatusTemplateController;
use App\Http\Controllers\Settings\VisaCaseStatusTemplateController;
use App\Http\Controllers\Settings\VisaCaseTaskTemplateController;
use App\Http\Controllers\Settings\VisaRequirementLibraryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/agency', [AgencyController::class, 'edit'])->name('settings.agency.edit');
    Route::patch('settings/agency', [AgencyController::class, 'update'])->name('settings.agency.update');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::get('settings/visa-requirements', [VisaRequirementLibraryController::class, 'index'])
        ->name('settings.visa-requirements.index');
    Route::post('settings/visa-requirements', [VisaRequirementLibraryController::class, 'store'])
        ->name('settings.visa-requirements.store');
    Route::patch('settings/visa-requirements/{visaRequirementTemplate}/reviewed', [VisaRequirementLibraryController::class, 'markReviewed'])
        ->name('settings.visa-requirements.review');

    Route::get('settings/task-templates', [VisaCaseTaskTemplateController::class, 'index'])
        ->name('settings.task-templates.index');
    Route::put('settings/task-templates', [VisaCaseTaskTemplateController::class, 'store'])
        ->name('settings.task-templates.store');

    Route::get('settings/visa-statuses', [VisaCaseStatusTemplateController::class, 'index'])
        ->name('settings.visa-statuses.index');
    Route::put('settings/visa-statuses', [VisaCaseStatusTemplateController::class, 'store'])
        ->name('settings.visa-statuses.store');

    Route::get('settings/task-statuses', [TaskStatusTemplateController::class, 'index'])
        ->name('settings.task-statuses.index');
    Route::put('settings/task-statuses', [TaskStatusTemplateController::class, 'store'])
        ->name('settings.task-statuses.store');
});
