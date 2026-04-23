<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPortalAuthController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\ClientPortalPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\VisaCaseController;
use App\Http\Controllers\VisaCaseRequirementController;
use App\Http\Middleware\EnsurePortalClientIsAuthenticated;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('portal/login', [ClientPortalAuthController::class, 'create'])->name('portal.login');
Route::post('portal/login', [ClientPortalAuthController::class, 'store'])->name('portal.login.store');
Route::post('portal/logout', [ClientPortalAuthController::class, 'destroy'])->name('portal.logout');

Route::middleware(EnsurePortalClientIsAuthenticated::class)
    ->prefix('portal/{portalToken}')
    ->whereUuid('portalToken')
    ->group(function () {
        Route::get('/', [ClientPortalController::class, 'show'])->name('portal.show');
        Route::patch('/profile', [ClientPortalController::class, 'update'])->name('portal.profile.update');
        Route::post('/attachments', [ClientPortalController::class, 'storeClientAttachment'])->name('portal.attachments.store');
        Route::post(
            '/visa-cases/{visaCase}/requirements/{requirement}/attachments',
            [ClientPortalController::class, 'storeRequirementAttachment'],
        )->name('portal.requirements.attachments.store');
        Route::get('/attachments/{attachment}', [ClientPortalController::class, 'downloadAttachment'])->name('portal.attachments.download');
    });

Route::prefix('portal/{portalToken}')
    ->whereUuid('portalToken')
    ->group(function () {
        Route::get('/password', [ClientPortalPasswordController::class, 'edit'])->name('portal.password.edit');
        Route::put('/password', [ClientPortalPasswordController::class, 'update'])->name('portal.password.update');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('clients', ClientController::class)->only(['index', 'show', 'store', 'update']);
    Route::resource('visa-cases', VisaCaseController::class)->only(['index', 'show', 'store', 'update']);
    Route::resource('tasks', TaskController::class)->only(['index', 'store', 'update']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('clients/{client}/notes', [NoteController::class, 'storeForClient'])->name('clients.notes.store');
    Route::post('visa-cases/{visaCase}/notes', [NoteController::class, 'storeForVisaCase'])->name('visa-cases.notes.store');
    Route::post('clients/{client}/attachments', [AttachmentController::class, 'storeForClient'])->name('clients.attachments.store');
    Route::post('visa-cases/{visaCase}/attachments', [AttachmentController::class, 'storeForVisaCase'])->name('visa-cases.attachments.store');
    Route::post('visa-cases/{visaCase}/requirements/{requirement}/attachments', [AttachmentController::class, 'storeForVisaCaseRequirement'])
        ->name('visa-cases.requirements.attachments.store');
    Route::patch('visa-cases/{visaCase}/requirements/{requirement}', [VisaCaseRequirementController::class, 'update'])
        ->name('visa-cases.requirements.update');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
