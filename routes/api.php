<?php

use App\Http\Controllers\Api\DesiredTopicController;
use App\Http\Controllers\Api\VitalsController;
use Illuminate\Support\Facades\Route;

Route::post('/vitals', [VitalsController::class, 'store']);

Route::middleware('auth:sanctum')->prefix('desired-topics')->name('desired-topics.')
    ->controller(DesiredTopicController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::delete('/{uuid}', 'destroy')->name('destroy');
    });
