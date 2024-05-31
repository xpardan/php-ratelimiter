<?php

namespace Ninkaki\RateLimiter\Policy\LimiterState;

interface LimiterStateInterface
{
    public function getId(): string;

    public function getExpirationTime(): ?int;
}