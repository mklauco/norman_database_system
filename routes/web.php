<?php

use App\Http\Controllers\ARBG\ARBGHomeController;
use App\Http\Controllers\ARBG\BacteriaController;
use App\Http\Controllers\ARBG\BacteriaStatisticsController;
use App\Http\Controllers\ARBG\GeneController;
use App\Http\Controllers\ARBG\GeneStatisticsController;
use App\Http\Controllers\Backend\Display\DisplayColumnController;
use App\Http\Controllers\Backend\Display\DisplaySectionController;
use App\Http\Controllers\Backend\FileController;
use App\Http\Controllers\Backend\GeneralController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\ProjectController;
use App\Http\Controllers\Backend\QueryLogController;
use App\Http\Controllers\Backend\ServerPaymentController;
use App\Http\Controllers\Backend\SystemSettingsController;
use App\Http\Controllers\Backend\TemplateController;
use App\Http\Controllers\Backend\UserLoginRetentionController;
use App\Http\Controllers\Bioassay\BioassayController;
use App\Http\Controllers\Bioassay\BioassayHomeController;
use App\Http\Controllers\Bioassay\StatisticsController as BioassayStatisticsController;
use App\Http\Controllers\Dashboard\DashboardMainController;
use App\Http\Controllers\DatabaseDirectoryController;
use App\Http\Controllers\Ecotox\EcotoxController;
use App\Http\Controllers\Ecotox\EcotoxCREDEvaluationController;
use App\Http\Controllers\Ecotox\EcotoxCREDEvaluationHomeController;
use App\Http\Controllers\Ecotox\EcotoxHomeController;
use App\Http\Controllers\Ecotox\EcotoxQualityController;
use App\Http\Controllers\Ecotox\LowestPNECController;
use App\Http\Controllers\Ecotox\PNECDerivationController;
use App\Http\Controllers\EmailTestController;
use App\Http\Controllers\Empodat\DataCollectionTemplateFileController;
use App\Http\Controllers\Empodat\EmpodatController;
use App\Http\Controllers\Empodat\EmpodatHomeController;
use App\Http\Controllers\Empodat\StationController;
use App\Http\Controllers\Empodat\StatisticsController as EmpodatStatisticsController;
use App\Http\Controllers\Empodat\UniqueSearchController;
use App\Http\Controllers\EmpodatSuspect\EmpodatSuspectController;
use App\Http\Controllers\EmpodatSuspect\EmpodatSuspectHomeController;
use App\Http\Controllers\EmpodatSuspect\StatisticsController as EmpodatSuspectStatisticsController;
use App\Http\Controllers\Factsheet\FactsheetController;
use App\Http\Controllers\Factsheet\FactsheetStatisticsController;
use App\Http\Controllers\Hazards\HazardsComptoxSubstanceDataController;
use App\Http\Controllers\Hazards\HazardsClassificationController;
use App\Http\Controllers\Hazards\HazardsDerivationController;
use App\Http\Controllers\Hazards\HazardsController;
use App\Http\Controllers\Hazards\HazardsHomeController;
use App\Http\Controllers\Hazards\HazardsDataController;
use App\Http\Controllers\Indoor\IndoorController;
use App\Http\Controllers\Indoor\IndoorHomeController;
use App\Http\Controllers\Indoor\IndoorStatisticsController;
use App\Http\Controllers\Literature\LiteratureController;
use App\Http\Controllers\Literature\LiteratureHomeController;
use App\Http\Controllers\Literature\StatisticsController as LiteratureStatisticsController;
use App\Http\Controllers\MainAPIController;
use App\Http\Controllers\Passive\PassiveController;
use App\Http\Controllers\Passive\PassiveHomeController;
use App\Http\Controllers\Passive\StatisticsController as PassiveStatisticsController;
use App\Http\Controllers\Prioritisation\ModellingDanubeController;
use App\Http\Controllers\Prioritisation\ModellingScarceController;
use App\Http\Controllers\Prioritisation\MonitoringDanubeController;
use App\Http\Controllers\Prioritisation\MonitoringScarceController;
use App\Http\Controllers\Prioritisation\PrioritisationHomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sars\SarsController;
use App\Http\Controllers\Sars\SarsHomeController;
use App\Http\Controllers\Sars\StatisticsController as SarsStatisticsController;
use App\Http\Controllers\SLE\SuspectListExchangeController;
use App\Http\Controllers\SLE\SuspectListExchangeHomeController;
use App\Http\Controllers\Susdat\DuplicateController;
use App\Http\Controllers\Susdat\SubstanceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return redirect()->route('landing.index');
// });

// Route::get('/landing', [DatabaseDirectoryController::class, 'index'])->name('landing.index');

Route::get('/', [DatabaseDirectoryController::class, 'index'])->name('home');

// Test route to check if global auth is working
Route::get('/test-public', function () {
    return 'This is a public test route';
})->name('test.public');

Route::prefix('backend')->middleware('auth')->group(function () {
    Route::get('overview', [DashboardMainController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

    // System Settings (admin and super_admin only)
    Route::prefix('system-settings')->middleware('role:super_admin|admin')->group(function () {
        Route::get('/', [SystemSettingsController::class, 'index'])->name('backend.system-settings.index');
    });

    Route::resource('users', UserController::class);
    Route::get('/user-data', [UserController::class, 'getUserData'])->middleware('auth');

    Route::resource('projects', ProjectController::class);

    Route::resource('templates', TemplateController::class);
    Route::get('templates/{template}/download', [TemplateController::class, 'download'])->name('templates.download');

    Route::resource('files', FileController::class);
    Route::get('/file-data', [FileController::class, 'getFileData'])->middleware('auth');
    // Specific templates for a database entity code

    Route::get('export-downloads', [App\Http\Controllers\Backend\ExportDownloadController::class, 'index'])->name('export_downloads.index');

    Route::resource('general_route', GeneralController::class);
    Route::resource('querylog', QueryLogController::class)->middleware('auth');

    // User Login Retention routes
    Route::prefix('user-login-retention')->middleware('role:super_admin')->group(function () {
        Route::get('filter', [UserLoginRetentionController::class, 'filter'])->name('backend.user-login-retention.filter');
        Route::get('search', [UserLoginRetentionController::class, 'search'])->name('backend.user-login-retention.search');
    });

    // Server Payments (CRUD)
    Route::prefix('server-payments')->group(function () {
        Route::middleware(['role:super_admin|server_payment_admin|server_payment_viewer'])->group(function () {
            Route::get('/', [ServerPaymentController::class, 'index'])->name('backend.server-payments.index');
        });
        Route::middleware(['role:super_admin|server_payment_admin'])->group(function () {
            Route::get('create', [ServerPaymentController::class, 'create'])->name('backend.server-payments.create');
            Route::post('/', [ServerPaymentController::class, 'store'])->name('backend.server-payments.store');
            Route::get('{serverPayment}/edit', [ServerPaymentController::class, 'edit'])->name('backend.server-payments.edit');
            Route::put('{serverPayment}', [ServerPaymentController::class, 'update'])->name('backend.server-payments.update');
            Route::delete('{serverPayment}', [ServerPaymentController::class, 'destroy'])->name('backend.server-payments.destroy');
        });
    });

    // Notification Management routes (super_admin only)
    Route::prefix('notifications')->middleware('role:super_admin')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('backend.notifications.index');
        Route::get('create', [NotificationController::class, 'create'])->name('backend.notifications.create');
        Route::post('/', [NotificationController::class, 'store'])->name('backend.notifications.store');
        Route::get('{notification}/edit', [NotificationController::class, 'edit'])->name('backend.notifications.edit');
        Route::put('{notification}', [NotificationController::class, 'update'])->name('backend.notifications.update');
        Route::delete('{notification}', [NotificationController::class, 'destroy'])->name('backend.notifications.destroy');
        Route::patch('{notification}/turn-off', [NotificationController::class, 'turnOff'])->name('backend.notifications.turn-off');
    });

    // Empodat Station Management routes (super_admin only)
    Route::prefix('empodat/stations')->middleware('role:super_admin')->group(function () {
        Route::get('/', [StationController::class, 'index'])->name('backend.empodat.stations.index');
        Route::get('create', [StationController::class, 'create'])->name('backend.empodat.stations.create');
        Route::post('/', [StationController::class, 'store'])->name('backend.empodat.stations.store');
        Route::get('{station}', [StationController::class, 'show'])->name('backend.empodat.stations.show');
        Route::get('{station}/edit', [StationController::class, 'edit'])->name('backend.empodat.stations.edit');
        Route::put('{station}', [StationController::class, 'update'])->name('backend.empodat.stations.update');
        Route::delete('{station}', [StationController::class, 'destroy'])->name('backend.empodat.stations.destroy');
    });

    // Display Configuration Management (super_admin and admin only)
    Route::prefix('display')->middleware('role:super_admin|admin')->group(function () {
        // Module overview
        Route::get('/', [DisplaySectionController::class, 'index'])->name('backend.display.index');

        // Sections for a module
        Route::get('{module}/sections', [DisplaySectionController::class, 'sections'])->name('backend.display.sections');
        Route::get('sections/{section}/edit', [DisplaySectionController::class, 'edit'])->name('backend.display.sections.edit');
        Route::put('sections/{section}', [DisplaySectionController::class, 'update'])->name('backend.display.sections.update');
        Route::post('{module}/sections/reorder', [DisplaySectionController::class, 'reorder'])->name('backend.display.sections.reorder');

        // Columns for a section
        Route::get('sections/{section}/columns', [DisplayColumnController::class, 'index'])->name('backend.display.columns.index');
        Route::get('columns/{column}/edit', [DisplayColumnController::class, 'edit'])->name('backend.display.columns.edit');
        Route::put('columns/{column}', [DisplayColumnController::class, 'update'])->name('backend.display.columns.update');
        Route::post('sections/{section}/columns/reorder', [DisplayColumnController::class, 'reorder'])->name('backend.display.columns.reorder');
    });
});

Route::prefix('backend')->group(function () {
    Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::post('files/{file}/rescan', [FileController::class, 'rescan'])->name('files.rescan');
    Route::get('templates/entity/{code}', [TemplateController::class, 'specificIndex'])->name('templates.specific.index');
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

Route::prefix('factsheets')->group(function () {
    Route::get('index', [FactsheetController::class, 'index'])->name('factsheets.index');
    Route::get('home', [App\Http\Controllers\Factsheet\FactsheetHomeController::class, 'index'])->name('factsheets.home.index');
    Route::get('countAll', [App\Http\Controllers\Factsheet\FactsheetHomeController::class, 'countAll'])->middleware('auth')->name('factsheets.countAll');

    Route::prefix('search')->group(function () {
        Route::get('filter/', [FactsheetController::class, 'filter'])->name('factsheets.search.filter');
        Route::get('search/', [FactsheetController::class, 'search'])->name('factsheets.search.search');
    });

    Route::get('show/{id}', [FactsheetController::class, 'show'])->name('factsheets.show');

    // Factsheet Statistics Routes
    Route::prefix('statistics')->middleware('auth')->group(function () {
        Route::post('populate-all', [FactsheetStatisticsController::class, 'populateAll'])->name('factsheets.statistics.populate-all');
        Route::post('generate-for-substance', [FactsheetStatisticsController::class, 'generateForSubstance'])->name('factsheets.statistics.generate-for-substance');
        Route::get('raw-json/{substance_id}', [FactsheetStatisticsController::class, 'showRawJson'])->name('factsheets.statistics.raw-json');
    });
});

Route::prefix('susdat')->group(function () {
    Route::get('substances/search/filter/', [SubstanceController::class, 'filter'])->name('substances.search.filter');
    Route::get('substances/search/search/', [SubstanceController::class, 'search'])->name('substances.search.search');
    Route::get('csv/start/{query_log_id}', [SubstanceController::class, 'startDownloadJob'])->name('susdat.csv.start');
    Route::get('csv/download/{filename}', [SubstanceController::class, 'downloadCsv'])->name('susdat.csv.download');
    Route::get('duplicates/filter/', [DuplicateController::class, 'filter'])->name('duplicates.filter');

    Route::get('duplicates/records/{pivot}/{pivot_value}', [DuplicateController::class, 'records'])->name('duplicates.records');
    Route::post('duplicates/records/handle', [DuplicateController::class, 'handleDuplicates'])->middleware('auth')->name('duplicates.handleDuplicates');
    Route::get('duplicates/merge-history', [DuplicateController::class, 'mergeHistory'])->name('duplicates.mergeHistory');
    Route::post('duplicates/{id}/restore', [DuplicateController::class, 'restore'])->middleware('auth')->name('duplicates.restore');

    Route::resource('substances', SubstanceController::class)->only(['index', 'show'])->whereNumber('substance');
    Route::resource('substances', SubstanceController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy'])->whereNumber('substance');
    Route::resource('duplicates', DuplicateController::class)->middleware('auth');

    Route::get('substances-audited', [SubstanceController::class, 'withAudits'])->name('substances.audited');
    Route::get('substances/{substance}/audits', [SubstanceController::class, 'audits'])->whereNumber('substance')->name('substances.audits');

    Route::middleware(['auth', 'role:super_admin'])->group(function () {
        Route::get('substances-missing-names', [SubstanceController::class, 'missingNames'])->name('substances.missing-names');
        Route::post('substances-fetch-missing-names', [SubstanceController::class, 'fetchMissingNames'])->name('substances.fetch-missing-names');
    });

    // Batch Conversion Routes
    Route::get('batch', [App\Http\Controllers\Susdat\BatchConversionController::class, 'index'])->name('susdat.batch.index');
    Route::post('batch/convert', [App\Http\Controllers\Susdat\BatchConversionController::class, 'convert'])->name('susdat.batch.convert');
    Route::get('batch/update', [App\Http\Controllers\Susdat\BatchConversionController::class, 'update'])->name('susdat.batch.update');
    Route::get('batch/download/csv', [App\Http\Controllers\Susdat\BatchConversionController::class, 'downloadCsv'])->name('susdat.batch.download.csv');
    Route::get('batch/download/xlsx', [App\Http\Controllers\Susdat\BatchConversionController::class, 'downloadXlsx'])->name('susdat.batch.download.xlsx');
});

Route::prefix('empodat')->group(function () {
    Route::get('search/filter/', [EmpodatController::class, 'filter'])->name('codsearch.filter');
    Route::get('search/search/', [EmpodatController::class, 'search'])->name('codsearch.search');
    Route::get('search/downloadjob/{query_log_id}', [EmpodatController::class, 'startDownloadJob'])->name('codsearch.download');
    Route::get('search/downloadidsjob/{query_log_id}', [EmpodatController::class, 'startDownloadIdsJob'])->name('codsearch.download.ids');
    Route::get('search/download/{filename}', [EmpodatController::class, 'downloadCsv'])
        ->name('csv.download');
    Route::resource('search', EmpodatController::class)->names([
        'index' => 'codsearch.index',
        'create' => 'codsearch.create',
        'store' => 'codsearch.store',
        'show' => 'codsearch.show',
        'edit' => 'codsearch.edit',
        'update' => 'codsearch.update',
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
    Route::post('unique/search/dbentity/lastupdate', [UniqueSearchController::class, 'updateDatabaseEntitiesLastUpdate'])->name('update.dbentities.lastupdate');

    Route::get('templates/entity/{code}', [EmpodatHomeController::class, 'specificIndex'])->name('empodat.templates');

    Route::prefix('statistics')->group(function () {
        // Main statistics overview
        Route::get('/', [EmpodatStatisticsController::class, 'index'])->name('empodat.statistics.index');

        // Country Year Statistics
        Route::get('country-year', [EmpodatStatisticsController::class, 'viewCountryStats'])->name('empodat.statistics.countryYear');
        Route::post('generate-country', [EmpodatStatisticsController::class, 'generateCountryStats'])->name('empodat.statistics.generateCountry');

        // Matrix Statistics
        Route::get('matrix', [EmpodatStatisticsController::class, 'matrix'])->name('empodat.statistics.matrix');
        Route::post('generate-matrix', [EmpodatStatisticsController::class, 'generateMatrixStats'])->name('empodat.statistics.generateMatrix');

        // Substance Statistics
        Route::get('substance', [EmpodatStatisticsController::class, 'substance'])->name('empodat.statistics.substance');
        Route::post('generate-substance', [EmpodatStatisticsController::class, 'generateSubstanceStats'])->name('empodat.statistics.generateSubstance');

        // Country Statistics (simple, without years)
        Route::get('country', [EmpodatStatisticsController::class, 'country'])->name('empodat.statistics.country');
        Route::post('generate-country-simple', [EmpodatStatisticsController::class, 'generateCountrySimpleStats'])->name('empodat.statistics.generateCountrySimple');

        // QA/QC Statistics (placeholder for future)
        Route::get('quality', [EmpodatStatisticsController::class, 'quality'])->name('empodat.statistics.quality');
        Route::post('generate-quality', [EmpodatStatisticsController::class, 'generateQualityStats'])->name('empodat.statistics.generateQuality');

        // CSV Downloads
        Route::get('download', [EmpodatStatisticsController::class, 'downloadCsv'])->name('empodat.statistics.download');

    });

});

Route::prefix('ecotox')->group(function () {
    Route::resource('ecotoxhome', EcotoxHomeController::class)->only(['index']);
    Route::resource('ecotoxhome', EcotoxHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::prefix('lowestpnec')->group(function () {
        Route::get('index', [LowestPNECController::class, 'index'])->name('ecotox.lowestpnec.index');
        Route::get('data', [LowestPNECController::class, 'getData'])->name('ecotox.lowestpnec.data');
        Route::get('search/', [LowestPNECController::class, 'search'])->name('ecotox.lowestpnec.search');
        Route::get('show/{sus_id}', [LowestPNECController::class, 'show'])->name('ecotox.lowestpnec.show');
        Route::get('countAll', [LowestPNECController::class, 'countAll'])->middleware('auth')->name('ecotox.lowestpnec.countAll');
        Route::post('csv/export', [LowestPNECController::class, 'startDownloadJob'])->middleware('auth')->name('ecotox.lowestpnec.csv.export');
        Route::get('csv/download/{filename}', [LowestPNECController::class, 'downloadCsv'])->name('ecotox.lowestpnec.csv.download');
    });

    Route::prefix('data')->group(function () {
        Route::get('search/filter/', [EcotoxController::class, 'filter'])->name('ecotox.data.search.filter');
        Route::get('search/search/', [EcotoxController::class, 'search'])->name('ecotox.data.search.search');
        Route::get('show/{id}', [EcotoxController::class, 'show'])->name('ecotox.data.show');
        Route::get('form/{id}', [EcotoxController::class, 'showForm'])->name('ecotox.data.form');
        Route::get('changes/{ecotoxId}/{columnName}', [EcotoxController::class, 'getChanges'])->name('ecotox.data.changes');
    });

    Route::get('e/countAll', [EcotoxController::class, 'countAll'])->middleware('auth')->name('ecotox.ecotox.countAll');
    Route::get('ee/countAll', [EcotoxHomeController::class, 'countAll'])->middleware('auth')->name('ecotox.countAll');
    Route::get('unique/search/substances', [EcotoxHomeController::class, 'syncNewSubstances'])->name('ecotox.unique.search.substances');
    Route::get('unique/search/substances/pnec3', [EcotoxHomeController::class, 'syncNewSubstancesPnec3'])->name('ecotox.unique.search.substances.pnec3');

    // CRED Evaluation routes
    Route::prefix('credevaluation')->middleware(['auth', 'role:super_admin|admin|ecotox'])->group(function () {
        Route::resource('home', EcotoxCREDEvaluationHomeController::class)->only(['index'])->names(['index' => 'ecotox.credevaluation.home.index']);
        Route::get('search/filter/', [EcotoxCREDEvaluationController::class, 'filter'])->name('ecotox.credevaluation.search.filter');
        Route::get('search/search/', [EcotoxCREDEvaluationController::class, 'search'])->name('ecotox.credevaluation.search.search');
        Route::get('countAll', [EcotoxCREDEvaluationController::class, 'countAll'])->middleware('auth')->name('ecotox.credevaluation.countAll');

        // CRED Evaluation Form Routes
        Route::get('form/{recordId}', [EcotoxCREDEvaluationController::class, 'showForm'])->name('ecotox.credevaluation.form');
        Route::get('demo', [EcotoxCREDEvaluationController::class, 'showDemoForm'])->name('ecotox.credevaluation.demo');

        // CRED Evaluation Modal Routes
        Route::get('data/{recordId}', [EcotoxCREDEvaluationController::class, 'getModalData'])->name('ecotox.credevaluation.modal.data');
        Route::get('history/{recordId}', [EcotoxCREDEvaluationController::class, 'getEvaluationHistory'])->name('ecotox.credevaluation.modal.history');
        Route::get('changes/{ecotoxId}/{columnName}', [EcotoxCREDEvaluationController::class, 'getChanges'])->name('ecotox.credevaluation.changes');
        Route::post('save', [EcotoxCREDEvaluationController::class, 'saveEvaluation'])->name('ecotox.credevaluation.modal.save');
    });

    // Quality Target routes
    Route::prefix('quality')->group(function () {
        Route::get('/', [EcotoxQualityController::class, 'index'])->name('ecotox.quality.index');
        Route::get('search/filter/', [EcotoxQualityController::class, 'filter'])->name('ecotox.quality.search.filter');
        Route::get('search/search/', [EcotoxQualityController::class, 'search'])->name('ecotox.quality.search.search');
        Route::get('form/{id}', [EcotoxQualityController::class, 'showForm'])->name('ecotox.quality.form');
        Route::get('changes/{pnecId}/{columnName}', [EcotoxQualityController::class, 'getChanges'])->name('ecotox.quality.changes');
    });

    // PNEC Derivation routes
    Route::prefix('pnecderivation')->group(function () {
        Route::get('/', [PNECDerivationController::class, 'index'])->name('ecotox.pnecderivation.index');
        Route::get('search/filter/', [PNECDerivationController::class, 'filter'])->name('ecotox.pnecderivation.search.filter');
        Route::get('search/search/', [PNECDerivationController::class, 'search'])->name('ecotox.pnecderivation.search.search');
        Route::get('countAll', [PNECDerivationController::class, 'countAll'])->middleware('auth')->name('ecotox.pnecderivation.countAll');
        Route::post('save-quality-votes', [PNECDerivationController::class, 'saveQualityVotes'])->middleware('auth')->name('ecotox.pnecderivation.saveQualityVotes');
    });

});

Route::prefix('sle')->group(function () {
    Route::get('slehome', [SuspectListExchangeHomeController::class, 'index'])->name('slehome.index');
    // Route::resource('slehome', SuspectListExchangeHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::get('slehome/countAll', [SuspectListExchangeHomeController::class, 'countAll'])->middleware('auth')->name('slehome.countAll');

    // CRUD routes for SuspectListExchangeSource
    Route::get('sources', [SuspectListExchangeController::class, 'main'])->name('sle.sources.index')->withoutMiddleware(['auth', 'role:admin|super_admin|sle']);
    Route::get('sources/database', [SuspectListExchangeController::class, 'index'])->name('sle.sources.database')->middleware('auth');
    Route::get('sources/refresh', [SuspectListExchangeController::class, 'refresh'])->name('sle.sources.refresh')->middleware('auth');
    Route::resource('sources', SuspectListExchangeController::class)->middleware('auth')->except(['index'])->names([
        'create' => 'sle.sources.create',
        'store' => 'sle.sources.store',
        'show' => 'sle.sources.show',
        'edit' => 'sle.sources.edit',
        'update' => 'sle.sources.update',
        'destroy' => 'sle.sources.destroy',
    ]);
});

Route::prefix('arbg')->group(function () {
    Route::resource('arbghome', ARBGHomeController::class)->only(['index']);
    Route::resource('arbghome', ARBGHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::get('countAll', [ARBGHomeController::class, 'countAll'])->middleware('auth')->name('arbg.countAll');
    Route::get('bacteria/countAll', [ARBGHomeController::class, 'countAllBacteria'])->middleware('auth')->name('arbg.bacteria.countAll');

    Route::prefix('bacteria')->group(function () {
        Route::get('search/filter/', [BacteriaController::class, 'filter'])->name('arbg.bacteria.search.filter');
        Route::get('search/search/', [BacteriaController::class, 'search'])->name('arbg.bacteria.search.search');
        Route::get('search/downloadjob/{query_log_id}', [BacteriaController::class, 'startDownloadJob'])->name('arbg.bacteria.search.download');
        Route::get('search/download/{filename}', [BacteriaController::class, 'downloadCsv'])
            ->name('arbg.bacteria.csv.download');
        Route::get('countAll', [ARBGHomeController::class, 'countAllBacteria'])->middleware('auth')->name('arbg.bacteria.countAll');

        // Statistics routes
        Route::prefix('statistics')->group(function () {
            Route::get('/', [BacteriaStatisticsController::class, 'index'])->name('arbg.bacteria.statistics.index');
            Route::get('per-country', [BacteriaStatisticsController::class, 'perCountry'])->name('arbg.bacteria.statistics.perCountry');
            Route::get('per-year', [BacteriaStatisticsController::class, 'perYear'])->name('arbg.bacteria.statistics.perYear');
            Route::get('per-matrix', [BacteriaStatisticsController::class, 'perMatrix'])->name('arbg.bacteria.statistics.perMatrix');
            Route::post('generate', [BacteriaStatisticsController::class, 'generateAll'])->middleware(['auth', 'role:super_admin|admin'])->name('arbg.bacteria.statistics.generate');
        });

        Route::get('{id}', [BacteriaController::class, 'show'])->name('arbg.bacteria.show');
    });

    Route::prefix('gene')->group(function () {
        Route::get('search/filter/', [GeneController::class, 'filter'])->name('arbg.gene.search.filter');
        Route::get('search/search/', [GeneController::class, 'search'])->name('arbg.gene.search.search');
        Route::get('search/downloadjob/{query_log_id}', [GeneController::class, 'startDownloadJob'])->name('arbg.gene.search.download');
        Route::get('search/download/{filename}', [GeneController::class, 'downloadCsv'])
            ->name('arbg.gene.csv.download');
        Route::get('countAll', [ARBGHomeController::class, 'countAllGene'])->middleware('auth')->name('arbg.gene.countAll');

        // Statistics routes
        Route::prefix('statistics')->group(function () {
            Route::get('/', [GeneStatisticsController::class, 'index'])->name('arbg.gene.statistics.index');
            Route::get('per-country', [GeneStatisticsController::class, 'perCountry'])->name('arbg.gene.statistics.perCountry');
            Route::get('per-year', [GeneStatisticsController::class, 'perYear'])->name('arbg.gene.statistics.perYear');
            Route::get('per-matrix', [GeneStatisticsController::class, 'perMatrix'])->name('arbg.gene.statistics.perMatrix');
            Route::post('generate', [GeneStatisticsController::class, 'generateAll'])->middleware(['auth', 'role:super_admin|admin'])->name('arbg.gene.statistics.generate');
        });

        Route::get('{id}', [GeneController::class, 'show'])->name('arbg.gene.show');
    });

});

Route::prefix('indoor')->group(function () {
    Route::resource('indoorhome', IndoorHomeController::class)->only(['index']);
    Route::resource('indoorhome', IndoorHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::get('search/filter/', [IndoorController::class, 'filter'])->name('indoor.search.filter');
    Route::get('search/search/', [IndoorController::class, 'search'])->name('indoor.search.search');
    Route::get('search/downloadjob/{query_log_id}', [IndoorController::class, 'startDownloadJob'])->name('indoor.search.download');
    Route::get('search/download/{filename}', [IndoorController::class, 'downloadCsv'])->name('indoor.csv.download');

    Route::get('indoor/countAll', [IndoorHomeController::class, 'countAll'])->middleware('auth')->name('indoor.countAll');

    // Statistics routes
    Route::prefix('statistics')->group(function () {
        Route::get('/', [IndoorStatisticsController::class, 'index'])->name('indoor.statistics.index');
        Route::get('per-country', [IndoorStatisticsController::class, 'perCountry'])->name('indoor.statistics.perCountry');
        Route::get('per-matrix', [IndoorStatisticsController::class, 'perMatrix'])->name('indoor.statistics.perMatrix');
        Route::get('per-environment-type', [IndoorStatisticsController::class, 'perEnvironmentType'])->name('indoor.statistics.perEnvironmentType');
        Route::get('per-environment-category', [IndoorStatisticsController::class, 'perEnvironmentCategory'])->name('indoor.statistics.perEnvironmentCategory');
        Route::post('generate', [IndoorStatisticsController::class, 'generateAll'])->middleware(['auth', 'role:super_admin|admin'])->name('indoor.statistics.generate');
    });

    Route::get('{id}', [IndoorController::class, 'show'])->name('indoor.show');
});

Route::prefix('passive')->group(function () {
    Route::resource('passivehome', PassiveHomeController::class)->only(['index']);
    Route::resource('passivehome', PassiveHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::get('search/filter/', [PassiveController::class, 'filter'])->name('passive.search.filter');
    Route::get('search/search/', [PassiveController::class, 'search'])->name('passive.search.search');
    Route::get('search/downloadjob/{query_log_id}', [PassiveController::class, 'startDownloadJob'])->name('passive.search.download');
    Route::get('search/download/{filename}', [PassiveController::class, 'downloadCsv'])
        ->name('passive.csv.download');
    Route::resource('search', PassiveController::class)->names([
        'index' => 'passive.search.index',
        'create' => 'passive.search.create',
        'store' => 'passive.search.store',
        'show' => 'passive.search.show',
        'edit' => 'passive.search.edit',
        'update' => 'passive.search.update',
        'destroy' => 'passive.search.destroy',
    ]);

    Route::get('passive/countAll', [PassiveHomeController::class, 'countAll'])->middleware('auth')->name('passive.countAll');

    // Statistics routes
    Route::prefix('statistics')->group(function () {
        // Public routes - anyone can view
        Route::get('/', [PassiveStatisticsController::class, 'index'])->name('passive.statistics.index');
        Route::get('per-country', [PassiveStatisticsController::class, 'perCountry'])->name('passive.statistics.perCountry');
        Route::get('per-matrix', [PassiveStatisticsController::class, 'perMatrix'])->name('passive.statistics.perMatrix');
        Route::get('per-substance', [PassiveStatisticsController::class, 'perSubstance'])->name('passive.statistics.perSubstance');

        // Admin-only route for generation
        Route::post('generate', [PassiveStatisticsController::class, 'generateStatistics'])
            ->middleware(['auth', 'role:super_admin|admin'])
            ->name('passive.statistics.generate');
    });
});

Route::prefix('bioassays')->group(function () {
    Route::resource('bioassayhome', BioassayHomeController::class)->only(['index']);
    Route::resource('bioassayhome', BioassayHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::get('search/filter/', [BioassayController::class, 'filter'])->name('bioassay.search.filter');
    Route::get('search/search/', [BioassayController::class, 'search'])->name('bioassay.search.search');
    Route::get('search/downloadjob/{query_log_id}', [BioassayController::class, 'startDownloadJob'])->name('bioassay.search.download');
    Route::get('search/download/{filename}', [BioassayController::class, 'downloadCsv'])->name('bioassay.csv.download');

    Route::resource('search', BioassayController::class)->names([
        'index' => 'bioassay.search.index',
        'create' => 'bioassay.search.create',
        'store' => 'bioassay.search.store',
        'show' => 'bioassay.search.show',
        'edit' => 'bioassay.search.edit',
        'update' => 'bioassay.search.update',
        'destroy' => 'bioassay.search.destroy',
    ]);

    Route::get('bioassay/countAll', [BioassayHomeController::class, 'countAll'])->middleware('auth')->name('bioassay.countAll');

    // Statistics routes
    Route::prefix('statistics')->group(function () {
        // Public routes - anyone can view
        Route::get('/', [BioassayStatisticsController::class, 'index'])->name('bioassay.statistics.index');
        Route::get('per-country', [BioassayStatisticsController::class, 'perCountry'])->name('bioassay.statistics.perCountry');
        Route::get('per-bioassay-name', [BioassayStatisticsController::class, 'perBioassayName'])->name('bioassay.statistics.perBioassayName');
        Route::get('per-endpoint', [BioassayStatisticsController::class, 'perEndpoint'])->name('bioassay.statistics.perEndpoint');
        Route::get('per-determinand', [BioassayStatisticsController::class, 'perDeterminand'])->name('bioassay.statistics.perDeterminand');
        Route::get('per-year', [BioassayStatisticsController::class, 'perYear'])->name('bioassay.statistics.perYear');

        // Admin-only route for generation
        Route::post('generate', [BioassayStatisticsController::class, 'generateStatistics'])
            ->middleware(['auth', 'role:super_admin|admin'])
            ->name('bioassay.statistics.generate');
    });
});

Route::prefix('sars')->group(function () {

    Route::get('search/filter/', [SarsController::class, 'filter'])->name('sars.search.filter');
    Route::get('search/search/', [SarsController::class, 'search'])->name('sars.search.search');
    Route::get('search/downloadjob/{query_log_id}', [SarsController::class, 'startDownloadJob'])->name('sars.search.download');
    Route::get('search/download/{filename}', [SarsController::class, 'downloadCsv'])
        ->name('sars.csv.download');
    Route::resource('search', SarsController::class)->names([
        'index' => 'sars.search.index',
        'create' => 'sars.search.create',
        'store' => 'sars.search.store',
        'show' => 'sars.search.show',
        'edit' => 'sars.search.edit',
        'update' => 'sars.search.update',
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

    // Statistics routes
    Route::prefix('statistics')->group(function () {
        // Public routes - anyone can view
        Route::get('/', [SarsStatisticsController::class, 'index'])->name('sars.statistics.index');
        Route::get('per-country', [SarsStatisticsController::class, 'perCountry'])->name('sars.statistics.perCountry');
        Route::get('per-matrix', [SarsStatisticsController::class, 'perMatrix'])->name('sars.statistics.perMatrix');
        Route::get('per-year', [SarsStatisticsController::class, 'perYear'])->name('sars.statistics.perYear');

        // Admin-only route for generation
        Route::post('generate', [SarsStatisticsController::class, 'generateStatistics'])
            ->middleware(['auth', 'role:super_admin|admin'])
            ->name('sars.statistics.generate');
    });
});

Route::prefix('literature')->group(function () {

    Route::get('search/filter/', [LiteratureController::class, 'filter'])->name('literature.search.filter');
    Route::get('search/search/', [LiteratureController::class, 'search'])->name('literature.search.search');
    Route::get('search/downloadjob/{query_log_id}', [LiteratureController::class, 'startDownloadJob'])->name('literature.search.download');
    Route::get('search/download/{filename}', [LiteratureController::class, 'downloadCsv'])
        ->name('literature.csv.download');
    Route::resource('search', LiteratureController::class)->names([
        'index' => 'literature.search.index',
        'create' => 'literature.search.create',
        'store' => 'literature.search.store',
        'show' => 'literature.search.show',
        'edit' => 'literature.search.edit',
        'update' => 'literature.search.update',
        'destroy' => 'literature.search.destroy',
    ]);

    // Public routes
    Route::resource('home', LiteratureHomeController::class)->only(['index'])->names([
        'index' => 'literature.home.index',
    ]);

    // Authenticated routes
    Route::resource('home', LiteratureHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy'])->names([
        'create' => 'literature.home.create',
        'store' => 'literature.home.store',
        'edit' => 'literature.home.edit',
        'update' => 'literature.home.update',
        'destroy' => 'literature.home.destroy',
    ]);

    // Count all records
    Route::get('literature/countAll', [LiteratureHomeController::class, 'countAll'])->middleware('auth')->name('literature.countAll');

    // Statistics routes
    Route::prefix('statistics')->group(function () {
        // Public routes - anyone can view
        Route::get('/', [LiteratureStatisticsController::class, 'index'])->name('literature.statistics.index');
        Route::get('per-country', [LiteratureStatisticsController::class, 'perCountry'])->name('literature.statistics.perCountry');
        Route::get('per-ecosystem', [LiteratureStatisticsController::class, 'perEcosystem'])->name('literature.statistics.perEcosystem');
        Route::get('per-class', [LiteratureStatisticsController::class, 'perClass'])->name('literature.statistics.perClass');
        Route::get('per-substance', [LiteratureStatisticsController::class, 'perSubstance'])->name('literature.statistics.perSubstance');

        // Admin-only route for generation
        Route::post('generate', [LiteratureStatisticsController::class, 'generateStatistics'])
            ->middleware(['auth', 'role:super_admin|admin'])
            ->name('literature.statistics.generate');
    });

    // Lookup Tables - Admin only routes
    Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
        // Life Stages
        Route::get('life_stages/download', [\App\Http\Controllers\Literature\LifeStageController::class, 'download'])->name('literature.life_stages.download');
        Route::resource('life_stages', \App\Http\Controllers\Literature\LifeStageController::class)->except(['destroy'])->names([
            'index' => 'literature.life_stages.index',
            'create' => 'literature.life_stages.create',
            'store' => 'literature.life_stages.store',
            'edit' => 'literature.life_stages.edit',
            'update' => 'literature.life_stages.update',
        ]);

        // Habitat Types
        Route::get('habitat_types/download', [\App\Http\Controllers\Literature\HabitatTypeController::class, 'download'])->name('literature.habitat_types.download');
        Route::resource('habitat_types', \App\Http\Controllers\Literature\HabitatTypeController::class)->except(['destroy'])->names([
            'index' => 'literature.habitat_types.index',
            'create' => 'literature.habitat_types.create',
            'store' => 'literature.habitat_types.store',
            'edit' => 'literature.habitat_types.edit',
            'update' => 'literature.habitat_types.update',
        ]);

        // Concentration Units
        Route::get('concentration_units/download', [\App\Http\Controllers\Literature\ConcentrationUnitController::class, 'download'])->name('literature.concentration_units.download');
        Route::resource('concentration_units', \App\Http\Controllers\Literature\ConcentrationUnitController::class)->except(['destroy'])->names([
            'index' => 'literature.concentration_units.index',
            'create' => 'literature.concentration_units.create',
            'store' => 'literature.concentration_units.store',
            'edit' => 'literature.concentration_units.edit',
            'update' => 'literature.concentration_units.update',
        ]);

        // Common Names
        Route::get('common_names/download', [\App\Http\Controllers\Literature\CommonNameController::class, 'download'])->name('literature.common_names.download');
        Route::resource('common_names', \App\Http\Controllers\Literature\CommonNameController::class)->except(['destroy'])->names([
            'index' => 'literature.common_names.index',
            'create' => 'literature.common_names.create',
            'store' => 'literature.common_names.store',
            'edit' => 'literature.common_names.edit',
            'update' => 'literature.common_names.update',
        ]);

        // Species
        Route::get('species/download', [\App\Http\Controllers\Literature\SpeciesController::class, 'download'])->name('literature.species.download');
        Route::resource('species', \App\Http\Controllers\Literature\SpeciesController::class)->except(['destroy'])->names([
            'index' => 'literature.species.index',
            'create' => 'literature.species.create',
            'store' => 'literature.species.store',
            'edit' => 'literature.species.edit',
            'update' => 'literature.species.update',
        ]);

        // Biota Sex
        Route::get('biota_sexs/download', [\App\Http\Controllers\Literature\BiotaSexController::class, 'download'])->name('literature.biota_sexs.download');
        Route::resource('biota_sexs', \App\Http\Controllers\Literature\BiotaSexController::class)->except(['destroy'])->names([
            'index' => 'literature.biota_sexs.index',
            'create' => 'literature.biota_sexs.create',
            'store' => 'literature.biota_sexs.store',
            'edit' => 'literature.biota_sexs.edit',
            'update' => 'literature.biota_sexs.update',
        ]);

        // Tissues
        Route::get('tissues/download-categories', [\App\Http\Controllers\Literature\TissueController::class, 'downloadCategories'])->name('literature.tissues.download_categories');
        Route::get('tissues/download-subcategories', [\App\Http\Controllers\Literature\TissueController::class, 'downloadSubcategories'])->name('literature.tissues.download_subcategories');
        Route::resource('tissues', \App\Http\Controllers\Literature\TissueController::class)->except(['destroy'])->names([
            'index' => 'literature.tissues.index',
            'create' => 'literature.tissues.create',
            'store' => 'literature.tissues.store',
            'edit' => 'literature.tissues.edit',
            'update' => 'literature.tissues.update',
        ]);

        // Type of Numeric Quantities
        Route::get('type_of_numeric_quantities/download', [\App\Http\Controllers\Literature\TypeOfNumericQuantityController::class, 'download'])->name('literature.type_of_numeric_quantities.download');
        Route::resource('type_of_numeric_quantities', \App\Http\Controllers\Literature\TypeOfNumericQuantityController::class)->except(['destroy'])->names([
            'index' => 'literature.type_of_numeric_quantities.index',
            'create' => 'literature.type_of_numeric_quantities.create',
            'store' => 'literature.type_of_numeric_quantities.store',
            'edit' => 'literature.type_of_numeric_quantities.edit',
            'update' => 'literature.type_of_numeric_quantities.update',
        ]);

        // Use Categories
        Route::get('use_categories/download', [\App\Http\Controllers\Literature\UseCategoryController::class, 'download'])->name('literature.use_categories.download');
        Route::resource('use_categories', \App\Http\Controllers\Literature\UseCategoryController::class)->except(['destroy'])->names([
            'index' => 'literature.use_categories.index',
            'create' => 'literature.use_categories.create',
            'store' => 'literature.use_categories.store',
            'edit' => 'literature.use_categories.edit',
            'update' => 'literature.use_categories.update',
        ]);
    });

});

// EmpodatSuspect Routes
Route::prefix('empodat_suspect')->group(function () {

    Route::get('search/filter/', [EmpodatSuspectController::class, 'filter'])->name('empodat_suspect.search.filter');
    Route::get('search/search/', [EmpodatSuspectController::class, 'search'])->name('empodat_suspect.search.search');
    Route::get('search/downloadjob/{query_log_id}', [EmpodatSuspectController::class, 'startDownloadJob'])->name('empodat_suspect.search.download');
    Route::get('search/download/{filename}', [EmpodatSuspectController::class, 'downloadCsv'])
        ->name('empodat_suspect.csv.download');
    Route::resource('search', EmpodatSuspectController::class)->names([
        'index' => 'empodat_suspect.search.index',
        'create' => 'empodat_suspect.search.create',
        'store' => 'empodat_suspect.search.store',
        'show' => 'empodat_suspect.search.show',
        'edit' => 'empodat_suspect.search.edit',
        'update' => 'empodat_suspect.search.update',
        'destroy' => 'empodat_suspect.search.destroy',
    ]);

    // Public routes
    Route::resource('home', EmpodatSuspectHomeController::class)->only(['index'])->names([
        'index' => 'empodat_suspect.home.index',
    ]);

    // Authenticated routes
    Route::resource('home', EmpodatSuspectHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy'])->names([
        'create' => 'empodat_suspect.home.create',
        'store' => 'empodat_suspect.home.store',
        'edit' => 'empodat_suspect.home.edit',
        'update' => 'empodat_suspect.home.update',
        'destroy' => 'empodat_suspect.home.destroy',
    ]);

    // Count all records
    Route::get('empodat_suspect/countAll', [EmpodatSuspectHomeController::class, 'countAll'])->middleware('auth')->name('empodat_suspect.countAll');

    // Statistics
    Route::prefix('statistics')->group(function () {
        // Main statistics overview
        Route::get('/', [EmpodatSuspectStatisticsController::class, 'index'])->name('empodat_suspect.statistics.index');

        // Generate all statistics
        Route::post('generate', [EmpodatSuspectStatisticsController::class, 'generateStatistics'])->name('empodat_suspect.statistics.generate');

        // Individual statistic views
        Route::get('substances-by-sample-code', [EmpodatSuspectStatisticsController::class, 'substancesBySampleCode'])->name('empodat_suspect.statistics.substancesBySampleCode');
        Route::get('records-by-sample-code', [EmpodatSuspectStatisticsController::class, 'recordsBySampleCode'])->name('empodat_suspect.statistics.recordsBySampleCode');
        Route::get('substances-by-country', [EmpodatSuspectStatisticsController::class, 'substancesByCountry'])->name('empodat_suspect.statistics.substancesByCountry');
        Route::get('records-by-country', [EmpodatSuspectStatisticsController::class, 'recordsByCountry'])->name('empodat_suspect.statistics.recordsByCountry');
        Route::get('records-by-confidence-interval', [EmpodatSuspectStatisticsController::class, 'recordsByConfidenceInterval'])->name('empodat_suspect.statistics.recordsByConfidenceInterval');
    });

});

Route::prefix('prioritisation')->group(function () {
    Route::resource('prioritisationhome', PrioritisationHomeController::class)->only(['index']);
    Route::resource('prioritisationhome', PrioritisationHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::get('/monitoring-scarce', [MonitoringScarceController::class, 'index'])->name('prioritisation.monitoring-scarce.index');
    Route::get('/monitoring-danube', [MonitoringDanubeController::class, 'index'])->name('prioritisation.monitoring-danube.index');

    Route::get('/modelling-scarce', [ModellingScarceController::class, 'index'])->name('prioritisation.modelling-scarce.index');
    Route::get('/modelling-scarce/{id}', [ModellingScarceController::class, 'show'])->name('prioritisation.modelling-scarce.show');
    Route::get('/modelling-danube', [ModellingDanubeController::class, 'index'])->name('prioritisation.modelling-danube.index');
    Route::get('/modelling-danube/{id}', [ModellingDanubeController::class, 'show'])->name('prioritisation.modelling-danube.show');

    Route::get('/monitoring-scarce/filter', [MonitoringScarceController::class, 'filter'])->name('prioritisation.monitoring-scarce.filter');
    Route::get('/monitoring-scarce/{id}', [MonitoringScarceController::class, 'show'])->name('prioritisation.monitoring-scarce.show');
    Route::get('/monitoring-danube/{id}', [MonitoringDanubeController::class, 'show'])->name('prioritisation.monitoring-danube.show');

    // CSV Download routes (require authentication)
    Route::get('/modelling-danube/download/csv', [ModellingDanubeController::class, 'downloadCsv'])->middleware('auth')->name('prioritisation.modelling-danube.csv');
    Route::get('/modelling-scarce/download/csv', [ModellingScarceController::class, 'downloadCsv'])->middleware('auth')->name('prioritisation.modelling-scarce.csv');
    Route::get('/monitoring-danube/download/csv', [MonitoringDanubeController::class, 'downloadCsv'])->middleware('auth')->name('prioritisation.monitoring-danube.csv');
    Route::get('/monitoring-scarce/download/csv', [MonitoringScarceController::class, 'downloadCsv'])->middleware('auth')->name('prioritisation.monitoring-scarce.csv');

    Route::get('prioritisation/countAll', [PrioritisationHomeController::class, 'countAll'])->middleware('auth')->name('prioritisation.countAll');
});

Route::prefix('hazards')->group(function () {
    Route::resource('hazardshome', HazardsHomeController::class)->only(['index']);
    Route::resource('hazardshome', HazardsHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::prefix('data')->group(function () {
        Route::get('search/filter/', [HazardsDataController::class, 'filter'])->name('hazards.data.search.filter');
        Route::get('search/search/', [HazardsDataController::class, 'search'])->name('hazards.data.search.search');
        Route::get('show/{id}', [HazardsDataController::class, 'show'])->name('hazards.data.show');
        Route::get('form/{id}', [HazardsDataController::class, 'showForm'])->name('hazards.data.form');
    });
});
Route::prefix('hazards')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::post('fetch', [HazardsController::class, 'fetch'])->name('hazards.fetch');
    Route::post('substance-data/fill', [HazardsComptoxSubstanceDataController::class, 'fill'])->name('hazards.substance_data.fill');
    Route::get('derivation/search/filter', [HazardsDerivationController::class, 'filter'])->name('hazards.derivation.search.filter');
    Route::get('derivation/search/search', [HazardsDerivationController::class, 'search'])->name('hazards.derivation.search.search');
    Route::get('derivation/{susdatSubstanceId}', [HazardsDerivationController::class, 'index'])->name('hazards.derivation.index');
    Route::post('derivation/vote', [HazardsDerivationController::class, 'vote'])->name('hazards.derivation.vote');
    Route::post('derivation/vote/remove', [HazardsDerivationController::class, 'removeVote'])->name('hazards.derivation.vote.remove');
    Route::get('derivation/substance-data/{id}', [HazardsDerivationController::class, 'substanceDataJson'])->name('hazards.derivation.substance-data.show');
    Route::get('derivation/selection/{selectionId}', [HazardsDerivationController::class, 'selectionJson'])->name('hazards.derivation.selection.show');
    Route::get('derivation/metadata/{selectionId}', [HazardsDerivationController::class, 'metadataShow'])->name('hazards.derivation.metadata.show');
    Route::get('derivation/metadata/{selectionId}/json', [HazardsDerivationController::class, 'metadataJson'])->name('hazards.derivation.metadata.json');
    Route::get('classification/search/filter', [HazardsClassificationController::class, 'filter'])->name('hazards.classification.search.filter');
    Route::get('classification/search/search', [HazardsClassificationController::class, 'search'])->name('hazards.classification.search.search');
    Route::get('classification/{susdatSubstanceId}', [HazardsClassificationController::class, 'index'])->name('hazards.classification.index');
    Route::post('classification/vote', [HazardsClassificationController::class, 'vote'])->name('hazards.classification.vote');
    Route::get('countAll', [HazardsHomeController::class, 'countAll'])->name('hazards.countAll');
});

Route::get('/send-test-email', [EmailTestController::class, 'sendTestEmail'])->middleware('auth');

require __DIR__.'/auth.php';


