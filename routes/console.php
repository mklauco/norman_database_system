<?php

use App\Jobs\Empodat\GenerateEmpodatStatisticsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune --hours=72')->daily();

Schedule::job(new GenerateEmpodatStatisticsJob)->dailyAt('00:00')->timezone('Europe/Bratislava')->name('empodat:generate-statistics');

Schedule::command('logs:clear')->days([1, 5])->at('03:00');

Schedule::command('exports:cleanup')->dailyAt('04:00');

Schedule::command('disposable:update')->weekly();
