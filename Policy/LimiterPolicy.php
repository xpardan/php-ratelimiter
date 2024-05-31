<?php

namespace Ninkaki\RateLimiter\Policy;

interface LimiterPolicy
{
    public function consume(): array;
}