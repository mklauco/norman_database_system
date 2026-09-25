<?php

use App\Http\Controllers\Api\v1\EmpodatController;
use App\Http\Controllers\Api\v1\SubstanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API v1 - NORMAN Database System
|
| Pattern: /api/v1/{module}/{parameter}/{value}
|
*/

Route::prefix('v1')->group(function () {
    // Substances (public)
    Route::prefix('substances')->group(function () {
        Route::get('code/{code}', [SubstanceController::class, 'getByCode']);
        Route::get('inchikey/{inchikey}', [SubstanceController::class, 'getByInchikey']);
    });

    // EMPODAT (authenticated)
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::prefix('empodat')->group(function () {
            Route::get('{search_type}/{search_value}', [EmpodatController::class, 'search'])
                ->where('search_type', 'substance|country');
        });
    });
});
