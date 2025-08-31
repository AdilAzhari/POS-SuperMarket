<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;

final class PerformanceService extends BaseService
{
    protected string $cachePrefix = 'performance:';

    public function getSlowQueries(int $limit = 50): array
    {
        $this->logInfo('Fetching slow queries');

        // Enable query logging
        DB::enableQueryLog();

        return $this->remember(
            'slow_queries',
            fn (): array => [
                'enabled' => config('app.debug'),
                'queries' => DB::getQueryLog(),
                'total_queries' => count(DB::getQueryLog()),
                'suggestions' => $this->getOptimizationSuggestions(),
            ],
            300
        );
    }

    public function getOptimizationSuggestions(): array
    {
        return [
            'Add indexes for frequently searched columns',
            'Use eager loading to prevent N+1 queries',
            'Implement pagination for large result sets',
            'Cache frequently accessed data',
            'Use select() to limit columns fetched',
            'Consider using database views for complex queries',
            'Optimize where clauses with proper indexing',
            'Use database-level aggregations instead of collection methods',
        ];
    }

    public function getCacheStats(): array
    {
        $this->logInfo('Fetching cache statistics');

        return $this->remember(
            'cache_stats',
            fn (): array => [
                'cache_driver' => config('cache.default'),
                'cache_prefix' => config('cache.prefix'),
                'suggestions' => [
                    'Use Redis for better performance',
                    'Set appropriate cache TTL values',
                    'Implement cache warming for critical data',
                    'Use cache tags for grouped invalidation',
                ],
            ],
            600
        );
    }

    public function getDatabaseMetrics(): array
    {
        $this->logInfo('Fetching database metrics');

        return $this->remember(
            'db_metrics',
            fn (): array => [
                'connection' => config('database.default'),
                'table_sizes' => $this->getTableSizes(),
                'index_usage' => $this->getIndexUsageStats(),
                'recommendations' => $this->getDatabaseRecommendations(),
            ],
            1800 // 30 minutes
        );
    }

    public function getMemoryUsage(): array
    {
        return [
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2).' MB',
            'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2).' MB',
            'suggestions' => [
                'Monitor memory usage in production',
                'Use generators for large datasets',
                'Implement proper pagination',
                'Clear unnecessary variables and objects',
            ],
        ];
    }

    private function getTableSizes(): array
    {
        try {
            $driver = config('database.default');
            $connection = config("database.connections.$driver");

            if ($connection['driver'] === 'mysql') {
                return DB::select('
                    SELECT
                        table_name,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                        table_rows
                    FROM information_schema.tables
                    WHERE table_schema = ?
                    ORDER BY size_mb DESC
                    LIMIT 20
                ', [config("database.connections.$driver.database")]);
            }

            return [];
        } catch (Exception $e) {
            $this->logError('Failed to get table sizes', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function getIndexUsageStats(): array
    {
        try {
            $driver = config('database.default');
            $connection = config("database.connections.$driver");

            if ($connection['driver'] === 'mysql') {
                return DB::select("
                    SELECT
                        table_name,
                        index_name,
                        cardinality
                    FROM information_schema.statistics
                    WHERE table_schema = ?
                    AND index_name != 'PRIMARY'
                    ORDER BY cardinality DESC
                    LIMIT 50
                ", [config("database.connections.$driver.database")]);
            }

            return [];
        } catch (Exception $e) {
            $this->logError('Failed to get index usage stats', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function getDatabaseRecommendations(): array
    {
        return [
            'Regular ANALYZE TABLE operations',
            'Monitor slow query log',
            'Optimize JOIN operations',
            'Use EXPLAIN to analyze query execution plans',
            'Consider partitioning for large tables',
            'Implement proper backup strategies',
            'Monitor connection pool usage',
            'Use read replicas for heavy read workloads',
        ];
    }
}
