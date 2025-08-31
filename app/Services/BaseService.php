<?php

declare(strict_types=1);

namespace App\Services;

use BadMethodCallException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    protected string $cachePrefix = '';

    protected int $cacheTime = 3600; // 1 hour default

    protected array $cacheTags = [];

    final public function clearServiceCache(): void
    {
        $this->flushAllCache();
        $this->logInfo('Service cache cleared');
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("[{$this->getServiceName()}] $message", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->getServiceName()}] $message", $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning("[{$this->getServiceName()}] $message", $context);
    }

    protected function cacheKey(string $key): string
    {
        return $this->cachePrefix.$key;
    }

    protected function remember(string $key, callable $callback, ?int $ttl = null, array $tags = [])
    {
        $allTags = array_merge($this->cacheTags, $tags);

        // Check if the current cache driver supports tagging
        if ($allTags !== [] && $this->cacheSupportsTagging()) {
            try {
                return Cache::tags($allTags)->remember(
                    $this->cacheKey($key),
                    $ttl ?? $this->cacheTime,
                    $callback
                );
            } catch (BadMethodCallException) {
                $this->logWarning('Cache tagging not supported, falling back to regular caching');
            }
        }

        // Fall back to regular caching without tags
        return Cache::remember(
            $this->cacheKey($key),
            $ttl ?? $this->cacheTime,
            $callback
        );
    }

    protected function forget(string $key): void
    {
        if ($this->cacheTags === [] || ! $this->cacheSupportsTagging()) {
            Cache::forget($this->cacheKey($key));
        } else {
            try {
                Cache::tags($this->cacheTags)->forget($this->cacheKey($key));
            } catch (BadMethodCallException) {
                Cache::forget($this->cacheKey($key));
            }
        }
    }

    protected function cacheSupportsTagging(): bool
    {
        $driver = config('cache.default');

        return in_array($driver, ['redis', 'memcached', 'array']);
    }

    protected function flushTag(string $tag): void
    {
        if ($this->cacheSupportsTagging()) {
            try {
                Cache::tags([$tag])->flush();
            } catch (BadMethodCallException) {
                $this->logWarning("Cache driver does not support tagging, cannot flush tag: $tag");
            }
        } else {
            $this->logWarning("Cache driver does not support tagging, cannot flush tag: $tag");
        }
    }

    protected function flushTags(array $tags): void
    {
        if ($this->cacheSupportsTagging()) {
            try {
                Cache::tags($tags)->flush();
            } catch (BadMethodCallException) {
                $this->logWarning('Cache driver does not support tagging, cannot flush tags: '.implode(', ', $tags));
            }
        } else {
            $this->logWarning('Cache driver does not support tagging, cannot flush tags: '.implode(', ', $tags));
        }
    }

    protected function flushAllCache(): void
    {
        if ($this->cacheTags !== [] && $this->cacheSupportsTagging()) {
            try {
                Cache::tags($this->cacheTags)->flush();
            } catch (BadMethodCallException) {
                $this->logWarning('Cache driver does not support tagging, cannot flush service cache');
            }
        } else {
            $this->logWarning('Cache driver does not support tagging, cannot flush service cache');
        }
    }

    protected function getServiceName(): string
    {
        return class_basename(static::class);
    }
}
