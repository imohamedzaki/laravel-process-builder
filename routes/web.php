<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use MohamedZaki\LaravelProcessBuilder\Http\Controllers\DashboardController;

Route::get('/', DashboardController::class)->name('dashboard');
