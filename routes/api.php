<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('substances/show/{substance}', [\App\Http\Controllers\Api\v1\SubstanceController::class, 'show']); 
    Route::get('substances/index', [\App\Http\Controllers\Api\v1\SubstanceController::class, 'index'])->middleware('auth:sanctum');; 
});     