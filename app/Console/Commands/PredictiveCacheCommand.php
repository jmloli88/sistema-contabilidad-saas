<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\Predictive\CacheServiceInterface;

/**
 * Console command for managing predictive analysis cache
 * 
 * Provides cache warming, cleaning, statistics, and health monitoring
 * for the intelligent caching system.
 */
class PredictiveCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'predictive:cache 
                            {action : Action to perform (warm|clean|stats|health|invalidate)}
                            {--type=* : Prediction types to target (income,expense,capacity,trends)}
                            {--force : Force action without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Manage predictive analysis cache (warm, clean, stats, health check)';

    public function __construct(
        private CacheServiceInterface $cacheService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        
        return match($action) {
            'warm' => $this->warmCache(),
            'clean' => $this->cleanCache(),
            'stats' => $this->showStatistics(),
            'health' => $this->checkHealth(),
            'invalidate' => $this->invalidateCache(),
            default => $this->showUsage()
        };
    }

    /**
     * Warm the cache with frequently accessed predictions
     */
    private function warmCache(): int
    {
        $types = $this->option('type') ?: ['income', 'expense', 'capacity', 'trends'];
        
        $this->info('Warming predictive cache...');
        $this->info('Target types: ' . implode(', ', $types));
        
        $progressBar = $this->output->createProgressBar(count($types));
        $progressBar->start();
        
        $totalWarmed = 0;
        
        foreach ($types as $type) {
            try {
                $warmed = $this->cacheService->warmCache([$type]);
                $totalWarmed += $warmed;
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("Failed to warm cache for type '{$type}': " . $e->getMessage());
            }
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("Cache warming completed. Warmed {$totalWarmed} entries.");
        
        return 0;
    }

    /**
     * Clean expired cache entries
     */
    private function cleanCache(): int
    {
        if (!$this->option('force') && !$this->confirm('This will remove all expired cache entries. Continue?')) {
            $this->info('Cache cleaning cancelled.');
            return 1;
        }
        
        $this->info('Cleaning expired cache entries...');
        
        try {
            $cleaned = $this->cacheService->cleanExpiredEntries();
            $this->info("Cleaned {$cleaned} expired cache entries.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to clean cache: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show cache statistics
     */
    private function showStatistics(): int
    {
        $this->info('Predictive Cache Statistics');
        $this->line('================================');
        
        try {
            $stats = $this->cacheService->getCacheStatistics();
            
            // Overall statistics
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Hit Rate', $stats['hit_rate_percentage'] . '%'],
                    ['Memory Hit Rate', $stats['memory_hit_rate'] . '%'],
                    ['Total Requests', number_format($stats['total_requests'])],
                    ['Memory Hits', number_format($stats['memory_hits'])],
                    ['Database Hits', number_format($stats['database_hits'])],
                    ['Cache Misses', number_format($stats['cache_misses'])],
                    ['Cache Writes', number_format($stats['cache_writes'])],
                    ['Cache Invalidations', number_format($stats['cache_invalidations'])],
                    ['Cache Health', $stats['cache_health'] ? 'Healthy' : 'Unhealthy'],
                ]
            );
            
            // Statistics by type
            if (!empty($stats['by_type'])) {
                $this->newLine();
                $this->info('Statistics by Prediction Type:');
                
                $typeData = [];
                foreach ($stats['by_type'] as $type => $typeStats) {
                    $totalTypeRequests = $typeStats['hits'] + $typeStats['misses'];
                    $typeHitRate = $totalTypeRequests > 0 ? 
                        round(($typeStats['hits'] / $totalTypeRequests) * 100, 2) : 0;
                    
                    $typeData[] = [
                        $type,
                        $typeHitRate . '%',
                        number_format($typeStats['hits']),
                        number_format($typeStats['misses']),
                        number_format($typeStats['writes'])
                    ];
                }
                
                $this->table(
                    ['Type', 'Hit Rate', 'Hits', 'Misses', 'Writes'],
                    $typeData
                );
            }
            
            // Database statistics
            if (!empty($stats['database_statistics'])) {
                $this->newLine();
                $this->info('Database Cache Statistics:');
                
                $dbData = [];
                foreach ($stats['database_statistics'] as $dbStat) {
                    $dbData[] = [
                        $dbStat->prediction_type,
                        number_format($dbStat->total_entries),
                        number_format($dbStat->active_entries),
                        round($dbStat->avg_ttl_minutes ?? 0, 1) . ' min'
                    ];
                }
                
                $this->table(
                    ['Type', 'Total Entries', 'Active Entries', 'Avg TTL'],
                    $dbData
                );
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to retrieve cache statistics: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check cache health
     */
    private function checkHealth(): int
    {
        $this->info('Checking cache health...');
        
        try {
            $isHealthy = $this->cacheService->isHealthy();
            $config = $this->cacheService->getConfiguration();
            
            if ($isHealthy) {
                $this->info('✅ Cache is healthy');
            } else {
                $this->error('❌ Cache is unhealthy');
            }
            
            $this->newLine();
            $this->info('Cache Configuration:');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Cache Duration', $config['cache_duration_minutes'] . ' minutes'],
                    ['Supported Types', implode(', ', $config['supported_types'])],
                    ['Memory Cache Prefix', $config['memory_cache_prefix']],
                    ['Database Table', $config['database_table']],
                    ['Health Status', $config['health_status'] ? 'Healthy' : 'Unhealthy']
                ]
            );
            
            return $isHealthy ? 0 : 1;
        } catch (\Exception $e) {
            $this->error('Failed to check cache health: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Invalidate cache entries
     */
    private function invalidateCache(): int
    {
        $types = $this->option('type');
        
        if (empty($types)) {
            if (!$this->option('force') && !$this->confirm('This will invalidate ALL cache entries. Continue?')) {
                $this->info('Cache invalidation cancelled.');
                return 1;
            }
            
            $invalidated = $this->cacheService->invalidateCache();
            $this->info("Invalidated {$invalidated} cache entries (all types).");
        } else {
            $totalInvalidated = 0;
            
            foreach ($types as $type) {
                try {
                    $invalidated = $this->cacheService->invalidateCache($type);
                    $totalInvalidated += $invalidated;
                    $this->info("Invalidated {$invalidated} cache entries for type '{$type}'.");
                } catch (\Exception $e) {
                    $this->error("Failed to invalidate cache for type '{$type}': " . $e->getMessage());
                }
            }
            
            $this->info("Total invalidated: {$totalInvalidated} cache entries.");
        }
        
        return 0;
    }

    /**
     * Show command usage
     */
    private function showUsage(): int
    {
        $this->error('Invalid action specified.');
        $this->newLine();
        $this->info('Available actions:');
        $this->line('  warm      - Warm cache with frequently accessed predictions');
        $this->line('  clean     - Clean expired cache entries');
        $this->line('  stats     - Show cache statistics');
        $this->line('  health    - Check cache health');
        $this->line('  invalidate - Invalidate cache entries');
        $this->newLine();
        $this->info('Examples:');
        $this->line('  php artisan predictive:cache warm --type=income,expense');
        $this->line('  php artisan predictive:cache clean --force');
        $this->line('  php artisan predictive:cache stats');
        $this->line('  php artisan predictive:cache health');
        $this->line('  php artisan predictive:cache invalidate --type=income');
        
        return 1;
    }
}