<?php

namespace Ninkaki\RateLimiter\Storage;

use Ninkaki\RateLimiter\Policy\LimiterState\LimiterStateInterface;

interface StorageInterface
{
    public function save(LimiterStateInterface $limiterState): void;

    public function fetch(string $limiterStateId): ?LimiterStateInterface;

    public function delete(string $limiterStateId): void;
}