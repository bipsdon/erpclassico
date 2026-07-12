<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CapacityController;
use App\Http\Controllers\Dashboard\DesignerController;
use App\Http\Controllers\Dashboard\HistoryController;
use App\Http\Controllers\Dashboard\PipelineManagerController;
use App\Http\Controllers\Dashboard\PrintingManagerController;
use App\Http\Controllers\Dashboard\SewingManagerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\Reports\StaffPerformanceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ─── Public ──────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));

// ─── Auth ────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Password reset (no email — token shown to admin, relayed manually)
    Route::get('/forgot-password',  [PasswordResetController::class, 'showForgotForm'])->name('password.forgot');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetToken'])->name('password.send-token');
    Route::get('/reset-password',   [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/reset-password',  [PasswordResetController::class, 'resetPassword'])->name('password.reset');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ─── Authenticated ───────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Smart redirect to role-specific dashboard
    Route::get('/dashboard', function () {
        $role = auth()->user()->role;

        return match ($role) {
            'pipeline_manager' => redirect()->route('dashboard.pipeline'),
            'designer'         => redirect()->route('dashboard.designer'),
            'printing_manager' => redirect()->route('dashboard.printing'),
            'sewing_manager'   => redirect()->route('dashboard.sewing'),
            default            => abort(403, 'Role not configured.'),
        };
    })->name('dashboard');

    // Pipeline Manager — full visibility
    Route::get('/dashboard/pipeline',
        [PipelineManagerController::class, 'index'])
        ->middleware('role:pipeline_manager')
        ->name('dashboard.pipeline');

    // Designer
    Route::get('/dashboard/designer',
        [DesignerController::class, 'index'])
        ->middleware('role:pipeline_manager,designer')
        ->name('dashboard.designer');

    // Printing Manager
    Route::get('/dashboard/printing',
        [PrintingManagerController::class, 'index'])
        ->middleware('role:pipeline_manager,printing_manager')
        ->name('dashboard.printing');

    // Sewing Manager
    Route::get('/dashboard/sewing',
        [SewingManagerController::class, 'index'])
        ->middleware('role:pipeline_manager,sewing_manager')
        ->name('dashboard.sewing');

    // ─── Staff Performance Report (pipeline manager only) ────
    Route::get('/reports/staff-performance',
        [StaffPerformanceController::class, 'index'])
        ->middleware('role:pipeline_manager')
        ->name('reports.staff-performance');

    // ─── History ──────────────────────────────────────────────
    Route::get('/history/{department?}',
        [HistoryController::class, 'index'])
        ->middleware('role:pipeline_manager,designer,printing_manager,sewing_manager')
        ->name('history.index');

    Route::get('/history/{department?}/export/xlsx',
        [HistoryController::class, 'exportXlsx'])
        ->middleware('role:pipeline_manager,designer,printing_manager,sewing_manager')
        ->name('history.export-xlsx');

    // ─── Orders CRUD ─────────────────────────────────────────
    Route::get('/orders/export/all-xlsx',
        [OrderController::class, 'exportAllXlsx'])
        ->middleware('role:pipeline_manager')
        ->name('orders.export.all-xlsx');

    Route::resource('orders', OrderController::class);

    // ─── Order duplicate ─────────────────────────────────────
    Route::post('/orders/{order}/duplicate',
        [OrderController::class, 'duplicate'])
        ->middleware('role:pipeline_manager')
        ->name('orders.duplicate');

    // ─── Order exports ───────────────────────────────────────
    Route::get('/orders/{order}/export/pdf',
        [OrderController::class, 'exportPdf'])
        ->middleware('role:pipeline_manager,designer,printing_manager,sewing_manager')
        ->name('orders.export.pdf');

    Route::get('/orders/{order}/export/xlsx',
        [OrderController::class, 'exportXlsx'])
        ->middleware('role:pipeline_manager,designer,printing_manager,sewing_manager')
        ->name('orders.export.xlsx');

    Route::get('/orders/export/all-xlsx',
        [OrderController::class, 'exportAllXlsx'])
        ->middleware('role:pipeline_manager')
        ->name('orders.export.all-xlsx');

    Route::get('/orders/export/all-xlsx',
        [OrderController::class, 'exportAllXlsx'])
        ->middleware('role:pipeline_manager')
        ->name('orders.export.all-xlsx');

    // ─── Quill editor image upload ────────────────────────────
    Route::post('/orders/editor-image', [\App\Http\Controllers\OrderImageController::class, 'store'])
        ->middleware('role:pipeline_manager')
        ->name('orders.editor-image');

    // Order attachment routes
    Route::get(
        '/orders/{order}/attachments/{attachment}/download',
        [OrderController::class, 'downloadAttachment']
    )->name('orders.attachments.download');

    Route::delete(
        '/orders/{order}/attachments/{attachment}',
        [OrderController::class, 'deleteAttachment']
    )->name('orders.attachments.delete');

    // ─── Production stage completion ─────────────────────────
    Route::patch('/production/{department}/{orderId}/complete',
        [ProductionController::class, 'complete'])
        ->middleware('role:pipeline_manager,designer,printing_manager,sewing_manager')
        ->name('production.complete');

    Route::patch('/production/{department}/{orderId}/start',
        [ProductionController::class, 'start'])
        ->middleware('role:pipeline_manager,designer,printing_manager,sewing_manager')
        ->name('production.start');

    Route::patch('/production/{order}/deliver',
        [ProductionController::class, 'deliver'])
        ->middleware('role:pipeline_manager')
        ->name('production.deliver');

    // ─── Users (pipeline manager only) ───────────────────────
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->name('users.toggle-active');

    // ─── Capacity Settings (pipeline manager only) ────────────
    Route::get('/capacity', [CapacityController::class, 'index'])->name('capacity.index');
    Route::put('/capacity', [CapacityController::class, 'update'])->name('capacity.update');

    // ─── Notifications ────────────────────────────────────────
    Route::get('/notifications',                                    [NotificationController::class, 'inbox'])->name('notifications.inbox');
    Route::get('/notifications/send',                              [NotificationController::class, 'create'])->name('notifications.create');
    Route::post('/notifications',                                  [NotificationController::class, 'store'])->name('notifications.store');
    Route::post('/notifications/mark-all-read',                    [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/unread-count',                      [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{notification}/read',              [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/{notification}/reply',             [NotificationController::class, 'reply'])->name('notifications.reply');

});
