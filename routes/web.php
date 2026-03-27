<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ────────────────────────────────────────────────────────────
Route::get('/', [ReportController::class, 'create'])->name('report.create');
Route::post('/report', [ReportController::class, 'store'])->name('report.store');
Route::get('/report/{report}/success', [ReportController::class, 'success'])->name('report.success');

// ─── Admin Auth ───────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Protected admin routes
    Route::middleware('auth')->group(function () {
        Route::get('/', [AdminReportController::class, 'index'])->name('dashboard');
        Route::get('/map', [AdminReportController::class, 'map'])->name('map');
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::post('/reports/{report}/approve', [AdminReportController::class, 'approve'])->name('reports.approve');
        Route::post('/reports/{report}/reject', [AdminReportController::class, 'reject'])->name('reports.reject');
    });
});
