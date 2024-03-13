<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use cebe\openapi\spec\OpenApi;
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
     *
     * @param OpenApi $openapi
     * @return \Generator<int, string, mixed, void>
     */
    public function generate(OpenApi $openapi = null): \Generator
    {
        echo 'start';
        yield 'middle';
        echo 'end';
    }
}
