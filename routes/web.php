<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\VisaCaseController;
use App\Http\Controllers\VisaCaseRequirementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('clients', ClientController::class)->only(['index', 'show', 'store', 'update']);
    Route::resource('visa-cases', VisaCaseController::class)->only(['index', 'show', 'store', 'update']);
    Route::resource('tasks', TaskController::class)->only(['index', 'store', 'update']);
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
