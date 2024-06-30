<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('substances/show/{id}', [\App\Http\Controllers\Api\Susdat\SubstanceAPI::class, 'show']); 