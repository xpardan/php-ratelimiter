<?php

namespace Ninkaki\RateLimiter\Config;

final class RateLimiterConfig
{
    private string $id;
    private string $policy;
    private int $limit;

    private \DateInterval $interval;

    public function getInterval(): \DateInterval
    {
        return $this->interval;
    }

    public function setInterval(\DateInterval $interval): void
    {
        $this->interval = $interval;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getPolicy(): string
    {
        return $this->policy;
    }

    public function setPolicy(string $policy): void
    {
        $this->policy = $policy;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }
}