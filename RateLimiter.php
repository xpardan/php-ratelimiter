<?php

namespace Ninkaki\RateLimiter;

use Ninkaki\RateLimiter\Policy\FixedWindowPolicy;
use Ninkaki\RateLimiter\Policy\LimiterPolicy;
use Ninkaki\RateLimiter\Policy\SlidingWindowPolicy;
use Ninkaki\RateLimiter\Storage\StorageInterface;
use Ninkaki\RateLimiter\Config\RateLimiterConfig;

class RateLimiter
{
    private RateLimiterConfig $config;
    private StorageInterface $storage;
    public function __construct(RateLimiterConfig $config, StorageInterface $storage)
    {
        $this->config = $config;
        $this->storage = $storage;
    }

    /**
     * @return LimiterPolicy|null
     * @throws \Exception
     */
    public function initLimiter(): ?LimiterPolicy
    {
        $policy = $this->config->getPolicy();
        if($policy === RateLimiterPolicy::FIXED_WINDOW) {
            return new FixedWindowPolicy($this->config->getId(), $this->config->getLimit(), $this->config->getInterval(), $this->storage);
        } else if($policy === RateLimiterPolicy::SLIDING_WINDOW) {
            return new SlidingWindowPolicy($this->config->getId(), $this->config->getLimit(), $this->config->getInterval(), $this->storage);
        } else {
            throw new \Exception("未知的策略类型 {$this->config->getPolicy()}, 当前仅支持 fixed window 与 sliding window.");
        }
    }
}