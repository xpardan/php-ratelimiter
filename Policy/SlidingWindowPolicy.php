<?php

namespace Ninkaki\RateLimiter\Policy;

use Ninkaki\RateLimiter\Policy\LimiterState\SlidingWindowState;
use Ninkaki\RateLimiter\Storage\StorageInterface;

class SlidingWindowPolicy implements LimiterPolicy
{
    private int $limit;
    private int $interval;
    private string $id;
    private StorageInterface $storage;

    public function __construct(string $id, int $limit, \DateInterval $interval, StorageInterface $storage)
    {
        $this->id = $id;
        $this->limit = $limit;
        $this->storage = $storage;

        $now = (new \DateTimeImmutable());
        $this->interval = $now->add($interval)->getTimestamp() - $now->getTimestamp();
    }

    public function attempt()
    {
        return $this->consume()['accepted'];
    }

    public function consume(): array
    {
        $tokens = 1;
        if ($tokens > $this->limit) {
            throw new \InvalidArgumentException(sprintf('Cannot reserve more tokens (%d) than the size of the rate limiter (%d).', $tokens, $this->limit));
        }

        // 根据窗口 ID 从 store 中取出窗口
        $window = $this->storage->fetch($this->id);

        if (!$window instanceof SlidingWindowState) {
            $window = new SlidingWindowState($this->id, $this->interval);
        } elseif ($window->isExpired()) {
            $window = SlidingWindowState::createFromPreviousWindow($window, $this->interval);
        }

        // 获取当前区间可用的 token 量
        $now = microtime(true);
        $hitCount = $window->getHitCount();
        $availableTokens = $this->getAvailableTokens($hitCount);

        //
        if ($availableTokens >= $tokens) {
            $window->add($tokens);

            $reservation = [
                'timeToAct' => $now,
                'availableTokens' => $this->getAvailableTokens($window->getHitCount()),
                'retryAfter' => \DateTimeImmutable::createFromFormat('U', floor($now)),
                'accepted' => true,
                'limit' => $this->limit,
            ];
        } else {
            $waitDuration = $window->calculateTimeForTokens($this->limit, $tokens);

            $window->add($tokens);

            $reservation = [
                'timeToAct' => $now + $waitDuration,
                'availableTokens' => $this->getAvailableTokens($window->getHitCount()),
                'retryAfter' => \DateTimeImmutable::createFromFormat('U', floor($now + $waitDuration)),
                'accepted' => false,
                'limit' => $this->limit,
            ];
        }

        $this->storage->save($window);

        return $reservation;
    }


    private function getAvailableTokens(int $hitCount): int
    {
        return $this->limit - $hitCount;
    }

}