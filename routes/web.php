<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Ecotox\EcotoxController;
use App\Http\Controllers\Empodat\EmpodatController;
use App\Http\Controllers\Susdat\SubstanceController;
use App\Http\Controllers\DatabaseDirectoryController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('databases')->middleware('auth')->group(function () {
    Route::get('/', [DatabaseDirectoryController::class, 'index'])->name('databases.index');
}); 

Route::prefix('susdat')->middleware('auth')->group(function () {
    Route::get('substancies/filter', [SubstanceController::class, 'filter'])->name('substancies.filter');
    Route::get('substancies/search', [SubstanceController::class, 'search'])->name('substancies.search');
    Route::resource('substancies', SubstanceController::class);
}); 

Route::prefix('empodat')->middleware('auth')->group(function () {
    Route::resource('general_route', EmpodatController::class);
    Route::get('general_route/filter', [SubstanceController::class, 'filter'])->name('general_route.filter');
}); 

Route::prefix('ecotox')->middleware('auth')->group(function () {
    Route::resource('general_route', EcotoxController::class);
    Route::get('general_route/filter', [SubstanceController::class, 'filter'])->name('general_route.filter');
}); 


require __DIR__.'/auth.php';
