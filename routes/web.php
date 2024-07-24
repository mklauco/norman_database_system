<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainAPIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\Susdat\SubstanceAPI;
use App\Http\Controllers\Ecotox\EcotoxController;
use App\Http\Controllers\Empodat\EmpodatController;
use App\Http\Controllers\Susdat\DuplicateController;
use App\Http\Controllers\Susdat\SubstanceController;
use App\Http\Controllers\DatabaseDirectoryController;
use App\Http\Controllers\Empodat\EmpodatHomeController;

Route::get('/', function () {
    return redirect()->route('landing.index');
});

Route::get('/landing', [DatabaseDirectoryController::class, 'index'])->name('landing.index');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/apiresources', [MainAPIController::class, 'index'])->name('apiresources.index');
    Route::post('/apiresources', [MainAPIController::class, 'store'])->name('apiresources.store');
    Route::delete('/apiresources/destroy', [MainAPIController::class, 'destroy'])->name('apiresources.destroy');
});

// Route::prefix('databases')->middleware('auth')->group(function () {
//     Route::get('/', [DatabaseDirectoryController::class, 'index'])->name('databases.index');
// }); 

Route::prefix('susdat')->group(function () {
    Route::get('substances/filter', [SubstanceController::class, 'filter'])->name('substances.filter');
    Route::get('substances/search', [SubstanceController::class, 'search'])->name('substances.search');
    Route::get('duplicates/filter/', [DuplicateController::class, 'filter'])->name('duplicates.filter');
    
    Route::get('duplicates/records/{pivot}/{pivot_value}', [DuplicateController::class, 'records'])->middleware('auth')->name('duplicates.records');
    Route::post('duplicates/records/handle', [DuplicateController::class, 'handleDuplicates'])->middleware('auth')->name('duplicates.handleDuplicates');

    Route::resource('substances', SubstanceController::class)->only(['index', 'show']);
    Route::resource('substances', SubstanceController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('duplicates', DuplicateController::class)->middleware('auth');
}); 

Route::prefix('empodat')->group(function () {
    Route::get('codsearch/filter/', [EmpodatController::class, 'filter'])->name('codsearch.filter');
    Route::get('codsearch/search/', [EmpodatController::class, 'filter'])->name('codsearch.search');

    Route::resource('codhome', EmpodatHomeController::class)->only(['index']);
    Route::resource('codhome', EmpodatHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('codsearch', EmpodatController::class);

    Route::get('general_route/filter', [SubstanceController::class, 'filter'])->name('general_route.filter');
}); 

Route::prefix('ecotox')->middleware('auth')->group(function () {
    Route::resource('general_route', EcotoxController::class);
    Route::get('general_route/filter', [SubstanceController::class, 'filter'])->name('general_route.filter');
}); 


require __DIR__.'/auth.php';
