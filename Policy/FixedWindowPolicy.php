<?php

namespace Ninkaki\RateLimiter\Policy;


use Ninkaki\RateLimiter\Policy\LimiterState\FixedWindowState;
use Ninkaki\RateLimiter\Storage\StorageInterface;

/**
 * 固定窗口
 */
class FixedWindowPolicy implements LimiterPolicy
{
    private string $id;
    private int $limit;
    private int $interval;
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
            throw new \InvalidArgumentException("Cannot reserve more tokens ({$tokens}) than the size of the rate limiter ({$this->limit}).");
        }

        // 根据窗口 ID 从 store 中取出窗口
        $window = $this->storage->fetch($this->id);

        if (!$window) {
            // 如果是第一次使用窗口，那么初始化一个新的窗口
            $window = new FixedWindowState($this->id, $this->interval, $this->limit);
        }

        // 获取当前区间可用的 Token 量
        $now = microtime(true);
        $availableTokens = $window->getAvailableTokens($now);

        // 可用 Token 大于消耗 Token
        if ($availableTokens >= $tokens) {
            $window->add($tokens, $now);

            $reservation = [
                'timeToAct' => $now,
                'availableTokens' => $window->getAvailableTokens($now),
                'retryAfter' => \DateTimeImmutable::createFromFormat('U', floor($now)),
                'accepted' => true,
                'limit' => $this->limit,
            ];
        } else {
            $window->add($tokens, $now);
            $waitDuration = $window->calculateTimeForTokens($tokens, $now);

            $reservation = [
                'timeToAct' => $now + $waitDuration,
                'availableTokens' => $window->getAvailableTokens($now),
                'retryAfter' => \DateTimeImmutable::createFromFormat('U', floor($now + $waitDuration)),
                'accepted' => false,
                'limit' => $this->limit,
            ];
        }

        $this->storage->save($window);

        return $reservation;
    }
}