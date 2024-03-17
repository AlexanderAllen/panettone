<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class BreadGenerator
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return \Generator<int, string, void, void>
     */
    public function generate(): \Generator
    {
        echo 'start';
        yield 'middle';
        echo 'end';
    }
}
