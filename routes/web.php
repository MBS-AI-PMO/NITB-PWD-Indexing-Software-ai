<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FolderController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Files & Documents System
    Route::get('/files', [DocumentController::class, 'index'])->name('files.index');
    Route::post('/files/upload', [DocumentController::class, 'store'])->name('files.upload');
    Route::put('/files/{document}', [DocumentController::class, 'update'])->name('files.update');
    Route::post('/files/{document}/re-extract', [DocumentController::class, 'reExtract'])->name('files.re-extract');
    Route::get('/files/{document}/debug-text', [DocumentController::class, 'debugExtractedText'])->name('files.debug-text');
    Route::delete('/files/{document}', [DocumentController::class, 'destroy'])->name('files.destroy');
    
    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
    
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::patch('/companies/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
    Route::post('/companies/{company}/login-as', [CompanyController::class, 'loginAs'])->name('companies.login-as');
    Route::post('/back-to-admin', [CompanyController::class, 'backToAdmin'])->name('back-to-admin');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    Route::get('/users', function () {
        return view('admin.users.index');
    })->name('users');

    Route::get('/account', function () {
        return view('admin.account.index');
    })->name('account.index');
});
