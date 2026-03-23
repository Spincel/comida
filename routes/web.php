<?php

use App\Http\Controllers\DashboardController; // Import new DashboardController
use App\Http\Controllers\DailyMenuController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\Admin\RoleController;
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

Route::get('/inicio', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard'); // Use DashboardController

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', function () {
        return Inertia::render('Admin/Index');
    })->name('admin.index');
});

Route::middleware(['auth', 'role:acquisitions_manager|admin'])->group(function () {
    Route::get('admin/history', [DashboardController::class, 'showGlobalHistory'])->name('admin.history');
    Route::get('admin/reports', [DashboardController::class, 'showGlobalReports'])->name('admin.reports');
    Route::get('admin/reports/export', [DashboardController::class, 'exportGlobalReports'])->name('admin.reports.export');

    Route::resource('users', UserController::class)
        ->middleware('permission:users.manage');
    Route::resource('areas', AreaController::class)
        ->middleware('permission:areas.manage');

    // Phase 2: Interface, Utilities, and Roles
    Route::middleware('auth')->group(function () {
        Route::get('admin/settings/interface', [SystemSettingsController::class, 'index'])
            ->name('admin.settings.interface')
            ->middleware('permission:system.settings');
        
        Route::get('admin/utilities/data', [ImportExportController::class, 'index'])
            ->name('admin.utilities.data')
            ->middleware('permission:system.settings');
        Route::get('admin/utilities/backup', [ImportExportController::class, 'backup'])
            ->name('admin.utilities.backup')
            ->middleware('permission:system.settings');
        Route::post('admin/utilities/import', [ImportExportController::class, 'import'])
            ->name('admin.utilities.import')
            ->middleware('permission:system.settings');
        Route::post('admin/utilities/import-sql', [ImportExportController::class, 'importSql'])
            ->name('admin.utilities.import.sql')
            ->middleware('permission:system.settings');
        Route::post('admin/utilities/truncate', [ImportExportController::class, 'truncate'])
            ->name('admin.utilities.truncate')
            ->middleware('permission:system.settings');
        
        Route::get('admin/settings/roles', [RoleController::class, 'index'])
            ->name('admin.settings.roles')
            ->middleware('permission:security.manage');
        Route::put('admin/settings/roles/{role}', [RoleController::class, 'update'])
            ->name('admin.settings.roles.update')
            ->middleware('permission:security.manage');

        Route::get('admin/sessions/logs', [DashboardController::class, 'showDeletionLogs'])
            ->name('admin.sessions.logs')
            ->middleware('permission:security.manage');
    });

    Route::resource('daily-menus', DailyMenuController::class);
    Route::post('daily-menus/publish-all', [DailyMenuController::class, 'publishAll'])->name('daily-menus.publishAll');
    Route::patch('daily-menus/{dailyMenu}/status', [DailyMenuController::class, 'updateStatus'])->name('daily-menus.updateStatus');
    Route::patch('daily-menus/provider-status', [DailyMenuController::class, 'updateProviderDailyStatus'])->name('daily-menus.updateProviderDailyStatus');
    Route::resource('providers', ProviderController::class);

    // New route for activating provider menu
    Route::post('dashboard/providers/{provider}/activate', [DashboardController::class, 'activateMenu'])->name('dashboard.providers.activate');
    // New route for deactivating provider menu
    Route::patch('dashboard/providers/{provider}/deactivate', [DashboardController::class, 'deactivateMenu'])->name('dashboard.providers.deactivate');
    Route::delete('dashboard/sessions/{session}', [DashboardController::class, 'destroySession'])->name('dashboard.sessions.destroy');
    Route::patch('dashboard/sessions/{session}/areas', [DashboardController::class, 'updateSessionAreas'])->name('dashboard.sessions.updateAreas');

    // New route for AI menu scanning
    Route::get('daily-menus/{provider}/existing', [DailyMenuController::class, 'getExistingItems'])->name('daily-menus.existing');
    Route::post('daily-menus/{provider}/scan-menu', [DailyMenuController::class, 'scanMenu'])->name('daily-menus.scan');
    Route::post('daily-menus/batch-store', [DailyMenuController::class, 'batchStore'])->name('daily-menus.batchStore');
});

Route::middleware(['auth'])->group(function () {
    // Shared Summary Routes
    Route::get('admin/orders-summary/{provider}/{date}/pdf', [DashboardController::class, 'generatePdfReport'])->name('admin.orders.summary.pdf');
    Route::get('admin/orders-summary/{provider}/{date}/{meal_type?}', [DashboardController::class, 'showOrderSummary'])->name('admin.orders.summary');
    Route::get('admin/send-order/{provider}/{date}', [DashboardController::class, 'showSendOrderView'])->name('admin.orders.send');

    Route::get('justification', [DashboardController::class, 'showJustificationPage'])->name('justification.index');
    Route::put('orders/{order}/justification', [OrderController::class, 'updateOwnJustification'])->name('orders.updateJustification');
    Route::post('sessions/{session}/evidence', [DashboardController::class, 'uploadEvidence'])->name('sessions.uploadEvidence');
    
    // Team Management for Area Managers
    Route::get('team', [UserController::class, 'indexTeam'])->name('team.index')->middleware('role:area_manager|admin');
    Route::post('team', [UserController::class, 'storeTeam'])->name('team.store')->middleware('role:area_manager|admin');
    Route::put('team/{user}', [UserController::class, 'updateTeam'])->name('team.update')->middleware('role:area_manager|admin');
    Route::patch('team/{user}/toggle-status', [UserController::class, 'toggleTeamStatus'])->name('team.toggleStatus')->middleware('role:area_manager|admin');

    Route::get('area/history', [DashboardController::class, 'showAreaHistory'])->name('area.history')->middleware('role:area_manager|admin');
    Route::get('area/reports', [DashboardController::class, 'showAreaReports'])->name('area.reports')->middleware('role:area_manager|admin');
    Route::get('daily-summary', [DashboardController::class, 'showDailySummary'])->name('daily.summary');

    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::put('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('orders/authorize-diners', [OrderController::class, 'authorizeDiners'])
        ->middleware('role:area_manager|admin|acquisitions_manager')
        ->name('orders.authorizeDiners');
    Route::post('orders/area-submit', [OrderController::class, 'submitAreaOrders'])
        ->middleware('role:area_manager|admin|acquisitions_manager')
        ->name('orders.areaSubmit');
    Route::put('orders/batch-justification', [OrderController::class, 'saveJustifications'])
        ->middleware('role:area_manager|admin')
        ->name('orders.saveJustifications');

    // Shared Configuration
    Route::get('admin/settings/reports', [SystemSettingsController::class, 'showReportSettings'])
        ->name('admin.settings.reports')
        ->middleware('role:admin|area_manager|acquisitions_manager');
    
    Route::post('admin/settings/interface', [SystemSettingsController::class, 'update'])
        ->name('admin.settings.interface.update')
        ->middleware('role:admin|area_manager|acquisitions_manager');
    
    // Theme and Background Management
    Route::post('settings/theme', [SystemSettingsController::class, 'updateUserTheme'])->name('settings.theme.update');
    Route::post('admin/settings/backgrounds/upload', [SystemSettingsController::class, 'uploadBackground'])->name('admin.settings.backgrounds.upload')->middleware('role:admin');

    // TEST ROUTE: Expediente Digital (Local Review Only)
    Route::get('admin/test/expediente/{provider}/{date}/{meal_type?}', [DashboardController::class, 'generateExpedienteReport'])->name('admin.test.expediente');
});

require __DIR__.'/auth.php';
