<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\HealthController;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\ProcessBackupsController;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\ProcessController;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\ProcessGenerateController;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\ProcessPreviewController;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\ProcessRollbackController;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\ProcessValidationController;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\ProjectController;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\ProjectControllersController;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\ProjectRoutesController;

Route::get('/health', HealthController::class)->name('health');

Route::prefix('project')->name('project.')->group(function (): void {
    Route::get('/', ProjectController::class)->name('index');
    Route::get('/routes', ProjectRoutesController::class)->name('routes');
    Route::get('/controllers', ProjectControllersController::class)->name('controllers');
});

Route::prefix('processes')->name('processes.')->group(function (): void {
    Route::get('/', [ProcessController::class, 'index'])->name('index');
    Route::post('/', [ProcessController::class, 'store'])->name('store');
    Route::get('/{process}', [ProcessController::class, 'show'])->name('show');
    Route::put('/{process}', [ProcessController::class, 'update'])->name('update');
    Route::delete('/{process}', [ProcessController::class, 'destroy'])->name('destroy');
    Route::post('/{process}/duplicate', [ProcessController::class, 'duplicate'])->name('duplicate');
    Route::post('/{process}/validate', ProcessValidationController::class)->name('validate');
    Route::post('/{process}/preview', ProcessPreviewController::class)->name('preview');
    Route::post('/{process}/generate', ProcessGenerateController::class)->name('generate');
    Route::get('/{process}/backups', ProcessBackupsController::class)->name('backups');
    Route::post('/{process}/backups/{backup}/rollback', ProcessRollbackController::class)->name('backups.rollback');
});
