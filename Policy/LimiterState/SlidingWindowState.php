<?php

namespace Ninkaki\RateLimiter\Policy\LimiterState;

use Ninkaki\RateLimiter\Policy\LimiterState\LimiterStateInterface;

class SlidingWindowState implements LimiterStateInterface
{
    private string $id;

    private int $hitCount = 0;
    private int $hitCountForLastWindow = 0;
    private int $intervalInSeconds;
    private float $windowEndAt;

    public function __construct(string $id, int $intervalInSeconds)
    {
        if ($intervalInSeconds < 1) {
            throw new \Exception("间隔值必须为正整数");
        }

        $this->id = $id;
        $this->intervalInSeconds = $intervalInSeconds;
        $this->windowEndAt = microtime(true) + $intervalInSeconds;
    }


    public function getId(): string
    {
        return $this->id;
    }

    public function getExpirationTime(): ?int
    {
        return (int)($this->windowEndAt + $this->intervalInSeconds - microtime(true));
    }


    public function isExpired(): bool
    {
        return microtime(true) > $this->windowEndAt;
    }

    public function add(int $hits = 1): void
    {
        $this->hitCount += $hits;
    }

    public static function createFromPreviousWindow(self $window, int $intervalInSeconds): self
    {
        $new = new self($window->id, $intervalInSeconds);
        $windowEndAt = $window->windowEndAt + $intervalInSeconds;

        if (microtime(true) < $windowEndAt) {
            $new->hitCountForLastWindow = $window->hitCount;
            $new->windowEndAt = $windowEndAt;
        }

        return $new;
    }

    /**
     * Calculates the sliding window number of request.
     */
    public function getHitCount(): int
    {
        $startOfWindow = $this->windowEndAt - $this->intervalInSeconds;
        $percentOfCurrentTimeFrame = min((microtime(true) - $startOfWindow) / $this->intervalInSeconds, 1);

        return (int)floor($this->hitCountForLastWindow * (1 - $percentOfCurrentTimeFrame) + $this->hitCount);
    }

    public function calculateTimeForTokens(int $maxSize, int $tokens): float
    {
        $remaining = $maxSize - $this->getHitCount();
        if ($remaining >= $tokens) {
            return 0;
        }

        $time = microtime(true);
        $startOfWindow = $this->windowEndAt - $this->intervalInSeconds;
        $timePassed = $time - $startOfWindow;
        $windowPassed = min($timePassed / $this->intervalInSeconds, 1);
        $releasable = max(1, $maxSize - floor($this->hitCountForLastWindow * (1 - $windowPassed)));
        $remainingWindow = $this->intervalInSeconds - $timePassed;
        $needed = $tokens - $remaining;

        if ($releasable >= $needed) {
            return $needed * ($remainingWindow / max(1, $releasable));
        }

        return ($this->windowEndAt - $time) + ($needed - $releasable) * ($this->intervalInSeconds / $maxSize);
    }
}