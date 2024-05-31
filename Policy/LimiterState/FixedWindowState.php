<?php

namespace Ninkaki\RateLimiter\Policy\LimiterState;

class FixedWindowState implements LimiterStateInterface
{
    private string $id;
    private int $hitCount = 0;
    private int $intervalInSeconds;
    private int $maxSize;
    private float $timer;

    public function __construct(string $id, int $intervalInSeconds, int $windowSize, ?float $timer = null)
    {
        $this->id = $id;
        $this->intervalInSeconds = $intervalInSeconds;
        $this->maxSize = $windowSize;
        $this->timer = $timer ?? microtime(true);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getExpirationTime(): ?int
    {
        return $this->intervalInSeconds;
    }

    public function getHitCount(): int
    {
        return $this->hitCount;
    }

    /**
     * @param int $hits
     * @param float|null $now
     * @return void
     */
    public function add(int $hits = 1, ?float $now = null): void
    {
        $now ??= microtime(true);
        if (($now - $this->timer) > $this->intervalInSeconds) {
            $this->timer = $now;
            $this->hitCount = 0;
        }

        $this->hitCount += $hits;
    }

    /**
     * @param float $now
     * @return int
     */
    public function getAvailableTokens(float $now): int
    {
        if (($now - $this->timer) > $this->intervalInSeconds) {
            return $this->maxSize;
        }

        return $this->maxSize - $this->hitCount;
    }

    /**
     * @param int $tokens
     * @param float $now
     * @return int
     */
    public function calculateTimeForTokens(int $tokens, float $now): int
    {
        if (($this->maxSize - $this->hitCount) >= $tokens) {
            return 0;
        }

        return (int) ceil($this->timer + $this->intervalInSeconds - $now);
    }
}