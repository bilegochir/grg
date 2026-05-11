<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\ApplicantPortalAuthController;
use App\Http\Controllers\ApplicantPortalController;
use App\Http\Controllers\ApplicantPortalInviteController;
use App\Http\Controllers\ApplicantNotificationPreferenceController;
use App\Http\Controllers\ApplicantNoteController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\LeadConversionController;
use App\Http\Controllers\LeadNoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\VisaCaseController;
use App\Http\Controllers\VisaCaseDocumentController;
use App\Http\Controllers\VisaCaseGroupController;
use App\Http\Controllers\VisaCaseMessageController;
use App\Http\Controllers\VisaCaseNoteController;
use App\Http\Controllers\VisaCaseTaskController;
use App\Http\Controllers\VisaFormTemplateController;
use App\Http\Controllers\WorkspaceBranchController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified', 'permission:dashboard.view'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])->middleware('permission:leads.view')->name('leads.index');
    Route::post('/leads', [LeadController::class, 'store'])->middleware('permission:leads.create')->name('leads.store');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('permission:leads.view')->name('leads.show');
    Route::patch('/leads/{lead}', [LeadController::class, 'update'])->middleware('permission:leads.update')->name('leads.update');
    Route::patch('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->middleware('permission:leads.update')->name('leads.status.update');
    Route::post('/leads/{lead}/notes', [LeadNoteController::class, 'store'])->middleware('permission:leads.update')->name('leads.notes.store');
    Route::post('/leads/{lead}/convert', [LeadConversionController::class, 'store'])->middleware('permission:leads.convert')->name('leads.convert');

    Route::get('/applicants', [ApplicantController::class, 'index'])->middleware('permission:applicants.view')->name('applicants.index');
    Route::get('/applicants/{applicant}', [ApplicantController::class, 'show'])->middleware('permission:applicants.view')->name('applicants.show');
    Route::post('/applicants/{applicant}/notes', [ApplicantNoteController::class, 'store'])->middleware('permission:applicants.update')->name('applicants.notes.store');
    Route::patch('/applicants/{applicant}/notification-preferences', [ApplicantNotificationPreferenceController::class, 'update'])->middleware('permission:applicants.update')->name('applicants.notification-preferences.update');
    Route::post('/applicants/{applicant}/portal-invites', [ApplicantPortalInviteController::class, 'store'])->middleware('permission:applicants.update')->name('applicants.portal-invites.store');

    Route::get('/cases', [VisaCaseController::class, 'index'])->middleware('permission:cases.view')->name('cases.index');
    Route::post('/cases', [VisaCaseController::class, 'store'])->middleware('permission:cases.create')->name('cases.store');
    Route::get('/cases/{case}', [VisaCaseController::class, 'show'])->middleware('permission:cases.view')->name('cases.show');
    Route::patch('/cases/{case}/stage', [VisaCaseController::class, 'updateStage'])->middleware('permission:cases.update')->name('cases.stage.update');
    Route::post('/cases/{case}/notes', [VisaCaseNoteController::class, 'store'])->middleware('permission:cases.update')->name('cases.notes.store');
    Route::post('/cases/{case}/messages', [VisaCaseMessageController::class, 'store'])->middleware('permission:communications.manage')->name('cases.messages.store');
    Route::post('/cases/{case}/tasks', [VisaCaseTaskController::class, 'store'])->middleware('permission:cases.update')->name('cases.tasks.store');
    Route::patch('/cases/{case}/tasks/{task}', [VisaCaseTaskController::class, 'update'])->middleware('permission:cases.update')->name('cases.tasks.update');
    Route::post('/cases/{case}/appointments', [AppointmentController::class, 'store'])->middleware('permission:cases.update')->name('cases.appointments.store');
    Route::post('/cases/{case}/appointments/{appointment}/remind', [AppointmentController::class, 'remind'])->middleware('permission:communications.manage')->name('cases.appointments.remind');
    Route::post('/cases/{case}/invoices', [InvoiceController::class, 'store'])->middleware('permission:finance.view')->name('cases.invoices.store');
    Route::post('/cases/{case}/documents/{document}/upload', [VisaCaseDocumentController::class, 'upload'])->middleware('permission:cases.update')->name('cases.documents.upload');
    Route::patch('/cases/{case}/documents/{document}/status', [VisaCaseDocumentController::class, 'updateStatus'])->middleware('permission:documents.review')->name('cases.documents.status.update');
    Route::get('/cases/{case}/documents/download-zip', [VisaCaseDocumentController::class, 'downloadZip'])->middleware('permission:cases.view')->name('cases.documents.zip');

    // Case Groups
    Route::post('/case-groups', [VisaCaseGroupController::class, 'store'])->middleware('permission:cases.update')->name('case-groups.store');
    Route::post('/case-groups/{group}/members', [VisaCaseGroupController::class, 'addMember'])->middleware('permission:cases.update')->name('case-groups.members.store');
    Route::delete('/case-groups/{group}/members/{case}', [VisaCaseGroupController::class, 'removeMember'])->middleware('permission:cases.update')->name('case-groups.members.destroy');
    Route::delete('/case-groups/{group}', [VisaCaseGroupController::class, 'destroy'])->middleware('permission:cases.update')->name('case-groups.destroy');

    // PDF Form Templates
    Route::post('/settings/form-templates', [VisaFormTemplateController::class, 'store'])->middleware('permission:settings.manage')->name('settings.form-templates.store');
    Route::patch('/settings/form-templates/{formTemplate}', [VisaFormTemplateController::class, 'update'])->middleware('permission:settings.manage')->name('settings.form-templates.update');
    Route::delete('/settings/form-templates/{formTemplate}', [VisaFormTemplateController::class, 'destroy'])->middleware('permission:settings.manage')->name('settings.form-templates.destroy');
    Route::get('/cases/{case}/form-templates/{formTemplate}/generate', [VisaFormTemplateController::class, 'generate'])->middleware('permission:cases.view')->name('cases.form-templates.generate');
    Route::post('/notifications/mark-seen', [NotificationController::class, 'markSeen'])->name('notifications.mark-seen');
    Route::post('/workspace/branch', [WorkspaceBranchController::class, 'update'])->middleware('permission:dashboard.view')->name('workspace.branch.update');
    Route::get('/appointments', [AppointmentController::class, 'index'])->middleware('permission:cases.view')->name('appointments.index');
    Route::get('/documents', [DocumentController::class, 'index'])->middleware('permission:documents.review')->name('documents.index');
    Route::get('/tasks', [TaskController::class, 'index'])->middleware('permission:cases.view')->name('tasks.index');
    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('permission:finance.view')->name('invoices.index');
    Route::get('/reports', ReportsController::class)->middleware('permission:dashboard.view')->name('reports.index');
    Route::get('/search/global', GlobalSearchController::class)->middleware('permission:dashboard.view')->name('search.global');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->middleware('permission:finance.view')->name('invoices.payments.store');
    Route::post('/invoices/{invoice}/remind', [InvoiceController::class, 'remind'])->middleware('permission:communications.manage')->name('invoices.remind');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/business', [SettingsController::class, 'updateBusiness'])->middleware('permission:settings.manage')->name('settings.business.update');
    Route::post('/settings/countries', [SettingsController::class, 'storeCountry'])->middleware('permission:settings.manage')->name('settings.countries.store');
    Route::patch('/settings/countries/{country}', [SettingsController::class, 'updateCountry'])->middleware('permission:settings.manage')->name('settings.countries.update');
    Route::delete('/settings/countries/{country}', [SettingsController::class, 'destroyCountry'])->middleware('permission:settings.manage')->name('settings.countries.destroy');
    Route::post('/settings/branches', [SettingsController::class, 'storeBranch'])->middleware('permission:settings.manage')->name('settings.branches.store');
    Route::patch('/settings/branches/{branch}', [SettingsController::class, 'updateBranch'])->middleware('permission:settings.manage')->name('settings.branches.update');
    Route::post('/settings/visa-types', [SettingsController::class, 'storeVisaType'])->middleware('permission:settings.manage')->name('settings.visa-types.store');
    Route::patch('/settings/visa-types/{visaType}', [SettingsController::class, 'updateVisaType'])->middleware('permission:settings.manage')->name('settings.visa-types.update');
    Route::delete('/settings/visa-types/{visaType}', [SettingsController::class, 'destroyVisaType'])->middleware('permission:settings.manage')->name('settings.visa-types.destroy');
    Route::post('/settings/document-templates', [SettingsController::class, 'storeDocumentTemplate'])->middleware('permission:settings.manage')->name('settings.document-templates.store');
    Route::patch('/settings/document-templates/{documentTemplate}', [SettingsController::class, 'updateDocumentTemplate'])->middleware('permission:settings.manage')->name('settings.document-templates.update');
    Route::delete('/settings/document-templates/{documentTemplate}', [SettingsController::class, 'destroyDocumentTemplate'])->middleware('permission:settings.manage')->name('settings.document-templates.destroy');
    Route::post('/settings/workflow-stages', [SettingsController::class, 'storeWorkflowStage'])->middleware('permission:settings.manage')->name('settings.workflow-stages.store');
    Route::patch('/settings/workflow-stages/{workflowStage}', [SettingsController::class, 'updateWorkflowStage'])->middleware('permission:settings.manage')->name('settings.workflow-stages.update');
    Route::delete('/settings/workflow-stages/{workflowStage}', [SettingsController::class, 'destroyWorkflowStage'])->middleware('permission:settings.manage')->name('settings.workflow-stages.destroy');
    Route::post('/settings/task-templates', [SettingsController::class, 'storeTaskTemplate'])->middleware('permission:settings.manage')->name('settings.task-templates.store');
    Route::patch('/settings/task-templates/{taskTemplate}', [SettingsController::class, 'updateTaskTemplate'])->middleware('permission:settings.manage')->name('settings.task-templates.update');
    Route::delete('/settings/task-templates/{taskTemplate}', [SettingsController::class, 'destroyTaskTemplate'])->middleware('permission:settings.manage')->name('settings.task-templates.destroy');
    Route::post('/settings/communication-templates', [SettingsController::class, 'storeCommunicationTemplate'])->middleware('permission:settings.manage')->name('settings.communication-templates.store');
    Route::patch('/settings/communication-templates/{communicationTemplate}', [SettingsController::class, 'updateCommunicationTemplate'])->middleware('permission:settings.manage')->name('settings.communication-templates.update');
    Route::delete('/settings/communication-templates/{communicationTemplate}', [SettingsController::class, 'destroyCommunicationTemplate'])->middleware('permission:settings.manage')->name('settings.communication-templates.destroy');

    Route::middleware('permission:staff.manage')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff/invitations', [TeamInvitationController::class, 'store'])->name('staff.invitations.store');
        Route::patch('/staff/{user}', [StaffController::class, 'updateUser'])->name('staff.users.update');
        Route::patch('/staff/roles/{role}', [StaffController::class, 'updateRole'])->name('staff.roles.update');
        Route::post('/staff/branches', [StaffController::class, 'storeBranch'])->name('staff.branches.store');
        Route::patch('/staff/branches/{branch}', [StaffController::class, 'updateBranch'])->name('staff.branches.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/documents/versions/{version}/download', [VisaCaseDocumentController::class, 'downloadVersion'])
    ->middleware('signed')
    ->name('cases.documents.versions.download');

Route::middleware('guest')->group(function () {
    Route::get('/team-invitations/{token}', [TeamInvitationController::class, 'create'])->name('team-invitations.accept');
    Route::post('/team-invitations/{token}', [TeamInvitationController::class, 'storeAcceptance'])->name('team-invitations.store');
    Route::get('/portal/login', [ApplicantPortalAuthController::class, 'create'])->name('portal.login');
    Route::post('/portal/login', [ApplicantPortalAuthController::class, 'store'])->name('portal.login.store');
    Route::get('/portal/access/{token}', [ApplicantPortalAuthController::class, 'accept'])->name('portal.accept');
});

Route::middleware('portal')->group(function () {
    Route::get('/portal', [ApplicantPortalController::class, 'dashboard'])->name('portal.dashboard');
    Route::get('/portal/cases/{case}', [ApplicantPortalController::class, 'showCase'])->name('portal.cases.show');
    Route::post('/portal/cases/{case}/documents/{document}/upload', [ApplicantPortalController::class, 'uploadDocument'])->name('portal.cases.documents.upload');
    Route::post('/portal/cases/{case}/messages', [ApplicantPortalController::class, 'storeMessage'])->name('portal.cases.messages.store');
    Route::post('/portal/logout', [ApplicantPortalAuthController::class, 'destroy'])->name('portal.logout');
});
