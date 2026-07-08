<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\RiwayatRevisiController;
use App\Http\Controllers\AdminGenerateController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // User Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::post('/excel', [DashboardController::class, 'bacaExcel'])->name('dashboard.excel');
    Route::post('/detect-placeholders', [DashboardController::class, 'detectPlaceholders'])->name('dashboard.detect-placeholders');
    Route::post('/generate', [DashboardController::class, 'generate'])->name('dashboard.generate');
    Route::get('/download/{id}', [DashboardController::class, 'downloadGenerated'])->name('dashboard.download');
    Route::post('/upload', [DashboardController::class, 'uploadBerkas'])->name('dashboard.upload');
    Route::post('/upload-revisi', [DashboardController::class, 'uploadRevisi'])->name('dashboard.upload-revisi');
    Route::get('/subfolders', [DashboardController::class, 'getSubfolders'])->name('dashboard.subfolders');
    Route::post('/scan-folder', [DashboardController::class, 'scanFolder'])->name('dashboard.scan-folder');

    // Status Berkas
    Route::get('/status', [StatusController::class, 'index'])->name('status.index');
    Route::get('/status/notif/{id}', [StatusController::class, 'downloadNotif'])->name('status.download-notif');

    // Monitoring Drive
    Route::get('/drive', [DriveController::class, 'index'])->name('drive.index');
    Route::match(['GET', 'POST'], '/drive/scan', [DriveController::class, 'scan'])->name('drive.scan');

    // Admin routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/drive-config', [AdminController::class, 'driveConfig'])->name('drive-config');
        Route::post('/upload-oauth-client', [AdminController::class, 'uploadOAuthClient'])->name('upload-oauth-client');
        Route::post('/save-drive-config', [AdminController::class, 'saveDriveConfig'])->name('save-drive-config');
        Route::get('/subfolders', [AdminController::class, 'getSubfolders'])->name('subfolders');
        Route::post('/scan-folder', [AdminController::class, 'scanFolder'])->name('scan-folder');
        Route::post('/approve/{id}', [AdminController::class, 'approve'])->name('approve');
        Route::post('/revision/{id}', [AdminController::class, 'requestRevision'])->name('revision');
        Route::post('/delete-upload/{id}', [AdminController::class, 'deleteUpload'])->name('delete-upload');
        Route::post('/hide-file', [AdminController::class, 'hideFile'])->name('hide-file');
        Route::post('/unhide-file/{fileId}', [AdminController::class, 'unhideFile'])->name('unhide-file');
        Route::get('/download/{id}', [AdminController::class, 'downloadDriveFile'])->name('download');
        Route::get('/preview/{id}', [AdminController::class, 'previewDriveFile'])->name('preview');

        // Google OAuth
        Route::get('/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.redirect');
        Route::get('/google/callback', [GoogleAuthController::class, 'handleCallback'])->name('google.callback');
        Route::post('/google/disconnect', [GoogleAuthController::class, 'disconnect'])->name('google.disconnect');

        // User management
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::post('/users/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('/users/toggle-active/{user}', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');

        // Generate Dokumen
        Route::get('/generate', [AdminGenerateController::class, 'index'])->name('generate');
        Route::post('/generate/fetch-spreadsheet', [AdminGenerateController::class, 'fetchSpreadsheet'])->name('generate.fetch-spreadsheet');
        Route::post('/generate/fetch-excel', [AdminGenerateController::class, 'fetchExcel'])->name('generate.fetch-excel');
        Route::post('/generate/generate', [AdminGenerateController::class, 'generate'])->name('generate.generate');
        Route::get('/generate/download/{id}', [AdminGenerateController::class, 'downloadHistory'])->name('generate.download');

        // Riwayat Revisi
        Route::get('/riwayat-revisi', [RiwayatRevisiController::class, 'index'])->name('riwayat-revisi');

        // Rekapitulasi
        Route::get('/rekapitulasi', [RiwayatRevisiController::class, 'rekapIndex'])->name('rekapitulasi');
        Route::get('/rekapitulasi/download/{user}', [RiwayatRevisiController::class, 'downloadRekap'])->name('rekapitulasi.download');
    });
});
