<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    protected string $cachePrefix = '';

    protected int $cacheTime = 3600; // 1 hour default

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

    protected function remember(string $key, callable $callback, ?int $ttl = null)
    {
        return Cache::remember(
            $this->cacheKey($key),
            $ttl ?? $this->cacheTime,
            $callback
        );
    }

    protected function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key));
    }

    protected function getServiceName(): string
    {
        return class_basename(static::class);
    }
}
