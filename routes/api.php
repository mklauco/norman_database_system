<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserApiController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('substances/show/{substance}', [\App\Http\Controllers\Api\v1\SubstanceController::class, 'show']); 
    Route::get('substances/index', [\App\Http\Controllers\Api\v1\SubstanceController::class, 'index'])->middleware('auth:sanctum');; 
});     

Route::middleware('auth:sanctum')->get('/users', [UserApiController::class, 'index']);


Route::get('/users-test', function() {
    return response()->json([
        'success' => true,
        'data' => [
            ['id' => 1, 'first_name' => 'Test', 'last_name' => 'User', 'email' => 'test@example.com']
        ]
    ]);
});