<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class Focaccia
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return \Generator<int>
     */
    public function generate(): \Generator
    {
        $this->logger->info('1');
        yield 1;
        yield 2;
        $this->logger->info('3');
        yield 3;
    }
}
