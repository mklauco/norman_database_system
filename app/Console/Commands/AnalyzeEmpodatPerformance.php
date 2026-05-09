<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeEmpodatPerformance extends Command
{
    protected $signature = 'empodat:analyze-performance {--slow-queries} {--indexes} {--table-stats}';

    protected $description = 'Analyze EMPODAT database performance and provide optimization recommendations';

    public function handle()
    {
        $this->info('🔍 EMPODAT Performance Analysis Report');
        $this->info('=====================================');

        if ($this->option('table-stats') || ! $this->hasOptions()) {
            $this->analyzeTableStatistics();
        }

        if ($this->option('indexes') || ! $this->hasOptions()) {
            $this->analyzeIndexUsage();
        }

        if ($this->option('slow-queries') || ! $this->hasOptions()) {
            $this->analyzeSlowQueries();
        }

        $this->provideOptimizationRecommendations();
    }

    private function hasOptions(): bool
    {
        return $this->option('slow-queries') || $this->option('indexes') || $this->option('table-stats');
    }

    private function analyzeTableStatistics()
    {
        $this->newLine();
        $this->info('📊 Table Statistics:');
        $this->info('===================');

        try {
            // Get table sizes
            $tableStats = DB::select("
                SELECT 
                    schemaname,
                    tablename,
                    attname,
                    n_distinct,
                    correlation
                FROM pg_stats 
                WHERE tablename IN ('empodat_main', 'empodat_stations', 'empodat_analytical_methods', 'empodat_data_sources')
                ORDER BY tablename, attname
            ");

            $this->table(['Schema', 'Table', 'Column', 'Distinct Values', 'Correlation'],
                collect($tableStats)->map(function ($stat) {
                    return [
                        $stat->schemaname,
                        $stat->tablename,
                        $stat->attname,
                        $stat->n_distinct ?? 'N/A',
                        number_format($stat->correlation ?? 0, 4),
                    ];
                })->toArray()
            );

            // Get table sizes
            $tableSizes = DB::select("
                SELECT 
                    table_name,
                    pg_size_pretty(pg_total_relation_size(quote_ident(table_name))) as size,
                    pg_total_relation_size(quote_ident(table_name)) as size_bytes
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name LIKE '%empodat%'
                ORDER BY pg_total_relation_size(quote_ident(table_name)) DESC
            ");

            $this->newLine();
            $this->info('💾 Table Sizes:');
            $this->table(['Table', 'Size'],
                collect($tableSizes)->map(function ($size) {
                    return [$size->table_name, $size->size];
                })->toArray()
            );

        } catch (\Exception $e) {
            $this->error('Could not analyze table statistics: '.$e->getMessage());
        }
    }

    private function analyzeIndexUsage()
    {
        $this->newLine();
        $this->info('🏷️  Index Usage Analysis:');
        $this->info('========================');

        try {
            $indexStats = DB::select("
                SELECT 
                    schemaname,
                    tablename,
                    indexname,
                    idx_tup_read,
                    idx_tup_fetch,
                    idx_scan
                FROM pg_stat_user_indexes 
                WHERE tablename LIKE '%empodat%'
                ORDER BY idx_scan DESC
            ");

            $this->table(['Schema', 'Table', 'Index', 'Tuples Read', 'Tuples Fetched', 'Scans'],
                collect($indexStats)->map(function ($index) {
                    return [
                        $index->schemaname,
                        $index->tablename,
                        $index->indexname,
                        number_format($index->idx_tup_read ?? 0),
                        number_format($index->idx_tup_fetch ?? 0),
                        number_format($index->idx_scan ?? 0),
                    ];
                })->toArray()
            );

            // Unused indexes
            $unusedIndexes = collect($indexStats)->filter(function ($index) {
                return ($index->idx_scan ?? 0) < 10;
            });

            if ($unusedIndexes->isNotEmpty()) {
                $this->newLine();
                $this->warn('⚠️  Potentially Unused Indexes (< 10 scans):');
                foreach ($unusedIndexes as $index) {
                    $this->line("  - {$index->tablename}.{$index->indexname}");
                }
            }

        } catch (\Exception $e) {
            $this->error('Could not analyze index usage: '.$e->getMessage());
        }
    }

    private function analyzeSlowQueries()
    {
        $this->newLine();
        $this->info('🐌 Slow Query Analysis:');
        $this->info('======================');

        try {
            // Check if pg_stat_statements is available
            $extensionExists = DB::select("
                SELECT 1 FROM pg_extension WHERE extname = 'pg_stat_statements'
            ");

            if (empty($extensionExists)) {
                $this->warn('pg_stat_statements extension not installed. Cannot analyze slow queries.');
                $this->info('To enable: CREATE EXTENSION pg_stat_statements;');

                return;
            }

            $slowQueries = DB::select("
                SELECT 
                    query,
                    calls,
                    total_time,
                    mean_time,
                    rows
                FROM pg_stat_statements 
                WHERE query LIKE '%empodat%'
                ORDER BY mean_time DESC 
                LIMIT 5
            ");

            if (empty($slowQueries)) {
                $this->info('No slow queries found containing "empodat".');

                return;
            }

            foreach ($slowQueries as $query) {
                $this->info('Query: '.substr($query->query, 0, 100).'...');
                $this->line('  Calls: '.number_format($query->calls));
                $this->line('  Avg Time: '.number_format($query->mean_time, 2).'ms');
                $this->line('  Total Time: '.number_format($query->total_time, 2).'ms');
                $this->line('  Avg Rows: '.number_format($query->rows / $query->calls));
                $this->newLine();
            }

        } catch (\Exception $e) {
            $this->error('Could not analyze slow queries: '.$e->getMessage());
        }
    }

    private function provideOptimizationRecommendations()
    {
        $this->newLine();
        $this->info('💡 Optimization Recommendations:');
        $this->info('================================');

        $recommendations = [
            '🔍 Index Optimization:',
            '  - Consider composite indexes for common filter combinations',
            '  - Add index on (matrix_id, substance_id, sampling_date_year)',
            '  - Add index on (station_id, matrix_id) for country-matrix filters',
            '',
            '⚡ Query Optimization:',
            '  - Use JOIN instead of whereHas() for better performance',
            '  - Implement query result caching for common searches',
            '  - Consider denormalizing frequently accessed data',
            '',
            '💾 Database Configuration:',
            '  - Increase shared_buffers (25% of RAM)',
            '  - Tune work_mem for complex queries (4-8MB)',
            '  - Enable auto_explain for query analysis',
            '  - Consider table partitioning by year or matrix_id',
            '',
            '🚀 Application Level:',
            '  - Implement Redis caching for search results',
            '  - Use database views for complex relationships',
            '  - Consider background job processing for large exports',
            '  - Implement cursor-based pagination for large datasets',
        ];

        foreach ($recommendations as $rec) {
            $this->line($rec);
        }
    }
}
