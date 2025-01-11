<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MainAPIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmailTestController;
use App\Http\Controllers\Ecotox\EcotoxController;
use App\Http\Controllers\Backend\GeneralController;
use App\Http\Controllers\Backend\ProjectController;
use App\Http\Controllers\Empodat\DCTItemController;
use App\Http\Controllers\Empodat\EmpodatController;
use App\Http\Controllers\Backend\QueryLogController;
use App\Http\Controllers\Susdat\DuplicateController;
use App\Http\Controllers\Susdat\SubstanceController;
use App\Http\Controllers\DatabaseDirectoryController;
use App\Http\Controllers\Ecotox\EcotoxHomeController;
use App\Http\Controllers\Empodat\EmpodatHomeController;
use App\Http\Controllers\Empodat\UniqueSearchController;
use App\Http\Controllers\Dashboard\DashboardMainController;
use App\Http\Controllers\SLE\SuspectListExchangeHomeController;
use App\Http\Controllers\ARGB\AntibioticResistanceBacteriaGeneHomeController;

Route::get('/', function () {
    return redirect()->route('landing.index');
});

Route::get('/landing', [DatabaseDirectoryController::class, 'index'])->name('landing.index');

Route::prefix('dashboard')->middleware('auth')->group(function () {
    Route::get('overview', [DashboardMainController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
    Route::resource('users', UserController::class);
    Route::resource('projects', ProjectController::class);
    
});

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
    
    Route::get('duplicates/records/{pivot}/{pivot_value}', [DuplicateController::class, 'records'])->name('duplicates.records');
    Route::post('duplicates/records/handle', [DuplicateController::class, 'handleDuplicates'])->middleware('auth')->name('duplicates.handleDuplicates');
    
    Route::resource('substances', SubstanceController::class)->only(['index', 'show']);
    Route::resource('substances', SubstanceController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('duplicates', DuplicateController::class)->middleware('auth');
}); 

Route::prefix('empodat')->group(function () {
    Route::get('codsearch/filter/{countrySearch?}/{matrixSearch?}/{sourceSearch?}/{year_from?}/{year_to?}/{displayOption?}', [EmpodatController::class, 'filter'])->name('codsearch.filter');
    Route::get('codsearch/filter/', [EmpodatController::class, 'filter'])->name('codsearch.filter');
    Route::get('codsearch/search/', [EmpodatController::class, 'search'])->name('codsearch.search');
    Route::get('codsearch/downloadjob/{query_log_id}', [EmpodatController::class, 'startDownloadJob'])->name('codsearch.download');
    Route::get('/codsearch/download/{filename}', [EmpodatController::class, 'downloadCsv'])
     ->name('csv.download');
    
    Route::resource('codhome', EmpodatHomeController::class)->only(['index']);
    Route::resource('codhome', EmpodatHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::get('dctitems/dctupload/{dctitem_id}', [DCTItemController::class, 'uploadNewTemplate'])->name('dctitems.upload_new_template');
    Route::post('dctitems/dctstore/{dctitem_id}', [DCTItemController::class, 'storeNewTemplate'])->name('dctitems.store_new_template');
    Route::get('dctitems/dctdownload/{id}', [DCTItemController::class, 'downloadTemplate'])->name('dctitems.donwload_template');
    Route::delete('dctitems/destroyfiles/{dctitem_id}', [DCTItemController::class, 'destroyFile'])->name('dctitems.delete_template');
    Route::get('dctitems/files/{id}', [DCTItemController::class, 'indexFiles'])->name('dctitems.index_files');
    Route::resource('dctitems', DCTItemController::class)->only(['index']);
    Route::resource('dctitems', DCTItemController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('codsearch', EmpodatController::class);

    // generate unique search tables
    Route::post('unique/search/country', [UniqueSearchController::class, 'countries'])->name('cod.unique.search.countries');
    Route::post('unique/search/matrix', [UniqueSearchController::class, 'matrices'])->name('cod.unique.search.matrices');


    Route::post('unique/search/dbentity', [UniqueSearchController::class, 'updateDatabaseEntitiesCounts'])->name('update.dbentities.counts');

    Route::resource('querylog', QueryLogController::class)->middleware('auth');
}); 

Route::prefix('ecotox')->group(function () {
    Route::resource('ecotoxhome', EcotoxHomeController::class)->only(['index']);;
    Route::resource('ecotoxhome', EcotoxHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
}); 

Route::prefix('sle')->group(function () {
    Route::resource('slehome', SuspectListExchangeHomeController::class)->only(['index']);
    Route::resource('slehome', SuspectListExchangeHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::get('slehome/countAll', [SuspectListExchangeHomeController::class, 'countAll'])->middleware('auth')->name('slehome.countAll');
}); 

Route::prefix('arbg')->group(function () {
    Route::resource('arbghome', AntibioticResistanceBacteriaGeneHomeController::class)->only(['index']);
    Route::resource('arbghome', AntibioticResistanceBacteriaGeneHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
}); 

Route::prefix('backend')->group(function () {
    Route::resource('general_route', GeneralController::class);
});


Route::get('/send-test-email', [EmailTestController::class, 'sendTestEmail'])->middleware('auth');

require __DIR__.'/auth.php';
