<?php

use App\Http\Controllers\Api\VitalsController;
use Illuminate\Support\Facades\Route;

Route::post('/vitals', [VitalsController::class, 'store']);
