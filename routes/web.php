<?php

use App\Http\Controllers\Bioassay\BioassayController;
use App\Http\Controllers\Bioassay\BioassayHomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MainAPIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmailTestController;
use App\Http\Controllers\Sars\SarsController;
use App\Http\Controllers\Sars\SarsHomeController;
use App\Http\Controllers\Backend\GeneralController;
use App\Http\Controllers\Backend\ProjectController;
use App\Http\Controllers\Empodat\EmpodatController;
use App\Http\Controllers\Backend\QueryLogController;
use App\Http\Controllers\Susdat\DuplicateController;
use App\Http\Controllers\Susdat\SubstanceController;
use App\Http\Controllers\DatabaseDirectoryController;
use App\Http\Controllers\Ecotox\EcotoxHomeController;
use App\Http\Controllers\Indoor\IndoorHomeController;
use App\Http\Controllers\Empodat\EmpodatHomeController;
use App\Http\Controllers\Passive\PassiveHomeController;
use App\Http\Controllers\Empodat\UniqueSearchController;
use App\Http\Controllers\Dashboard\DashboardMainController;
use App\Http\Controllers\SLE\SuspectListExchangeHomeController;
use App\Http\Controllers\Empodat\DataCollectionTemplateFileController;
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
    Route::get('search/filter/', [EmpodatController::class, 'filter'])->name('codsearch.filter');
    Route::get('search/search/', [EmpodatController::class, 'search'])->name('codsearch.search');
    Route::get('search/downloadjob/{query_log_id}', [EmpodatController::class, 'startDownloadJob'])->name('codsearch.download');
    Route::get('search/download/{filename}', [EmpodatController::class, 'downloadCsv'])
    ->name('csv.download');
    Route::resource('search', EmpodatController::class)->names([
        'index'   => 'codsearch.index',
        'create'  => 'codsearch.create',
        'store'   => 'codsearch.store',
        'show'    => 'codsearch.show',
        'edit'    => 'codsearch.edit',
        'update'  => 'codsearch.update',
        'destroy' => 'codsearch.destroy',
    ]);

    // Public routes
    Route::resource('home', EmpodatHomeController::class)->only(['index'])->names([
        'index' => 'codhome.index',
    ]);

    // Authenticated routes
    Route::resource('home', EmpodatHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy'])->names([
        'create' => 'codhome.create',
        'store' => 'codhome.store',
        'edit' => 'codhome.edit',
        'update' => 'codhome.update',
        'destroy' => 'codhome.destroy',
    ]);
    Route::get('dctitems/dctupload/{dctitem_id}', [DataCollectionTemplateFileController::class, 'uploadNewTemplate'])->name('dctitems.upload_new_template');
    Route::post('dctitems/dctstore/{dctitem_id}', [DataCollectionTemplateFileController::class, 'storeNewTemplate'])->name('dctitems.store_new_template');
    Route::get('dctitems/dctdownload/{id}', [DataCollectionTemplateFileController::class, 'downloadTemplate'])->name('dctitems.donwload_template');
    Route::delete('dctitems/destroyfiles/{dctitem_id}', [DataCollectionTemplateFileController::class, 'destroyFile'])->name('dctitems.delete_template');
    Route::get('dctitems/files/{id}', [DataCollectionTemplateFileController::class, 'indexFiles'])->name('dctitems.index_files');
    Route::resource('dctitems', DataCollectionTemplateFileController::class)->only(['index']);
    Route::resource('dctitems', DataCollectionTemplateFileController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);


    // generate unique search tables
    Route::post('unique/search/country', [UniqueSearchController::class, 'countries'])->name('cod.unique.search.countries');
    Route::post('unique/search/matrix', [UniqueSearchController::class, 'matrices'])->name('cod.unique.search.matrices');


    Route::post('unique/search/dbentity', [UniqueSearchController::class, 'updateDatabaseEntitiesCounts'])->name('update.dbentities.counts');

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

Route::prefix('indoor')->group(function () {
    Route::resource('indoorhome', IndoorHomeController::class)->only(['index']);
    Route::resource('indoorhome', IndoorHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
});

Route::prefix('passive')->group(function () {
    Route::resource('passivehome', PassiveHomeController::class)->only(['index']);
    Route::resource('passivehome', PassiveHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
});

Route::prefix('bioassays')->group(function () {
    Route::resource('bioassayhome', BioassayHomeController::class)->only(['index']);
    Route::resource('bioassayhome', BioassayHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::get('search/filter/', [BioassayController::class, 'filter'])->name('bioassay.search.filter');
    Route::get('search/search/', [BioassayController::class, 'search'])->name('bioassay.search.search');

    Route::resource('search', BioassayController::class)->names([
        'index'   => 'bioassay.search.index',
        'create'  => 'bioassay.search.create',
        'store'   => 'bioassay.search.store',
        'show'    => 'bioassay.search.show',
        'edit'    => 'bioassay.search.edit',
        'update'  => 'bioassay.search.update',
        'destroy' => 'bioassay.search.destroy',
    ]);
});

Route::prefix('sars')->group(function () {

    Route::get('search/filter/', [SarsController::class, 'filter'])->name('sars.search.filter');
    Route::get('search/search/', [SarsController::class, 'search'])->name('sars.search.search');
    Route::get('search/downloadjob/{query_log_id}', [SarsController::class, 'startDownloadJob'])->name('sars.search.download');
    Route::get('search/download/{filename}', [SarsController::class, 'downloadCsv'])
    ->name('sars.csv.download');
    Route::resource('search', SarsController::class)->names([
        'index'   => 'sars.search.index',
        'create'  => 'sars.search.create',
        'store'   => 'sars.search.store',
        'show'    => 'sars.search.show',
        'edit'    => 'sars.search.edit',
        'update'  => 'sars.search.update',
        'destroy' => 'sars.search.destroy',
    ]);

    // Public routes
    Route::resource('home', SarsHomeController::class)->only(['index'])->names([
        'index' => 'sars.home.index',
    ]);

    // Authenticated routes
    Route::resource('home', SarsHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy'])->names([
        'create' => 'sars.home.create',
        'store' => 'sars.home.store',
        'edit' => 'sars.home.edit',
        'update' => 'sars.home.update',
        'destroy' => 'sars.home.destroy',
    ]);


});

Route::prefix('backend')->group(function () {
    Route::resource('general_route', GeneralController::class);
    Route::resource('querylog', QueryLogController::class)->middleware('auth');
});


Route::get('/send-test-email', [EmailTestController::class, 'sendTestEmail'])->middleware('auth');

require __DIR__.'/auth.php';
