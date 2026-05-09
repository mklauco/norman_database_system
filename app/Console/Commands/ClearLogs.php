<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application log files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logPath = storage_path('logs');

        if (! is_dir($logPath)) {
            $this->error('Logs directory not found');

            return 1;
        }

        $files = glob($logPath.'/*.log');

        foreach ($files as $file) {
            if (is_file($file)) {
                file_put_contents($file, '');
                $this->info('Cleared: '.basename($file));
            }
        }

        $this->info('All log files have been cleared');

        return 0;
    }
}
