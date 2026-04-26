<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune --hours=72')->daily();

Schedule::command('logs:clear')->days([1, 5])->at('03:00');

Schedule::command('exports:cleanup')->dailyAt('04:00');
