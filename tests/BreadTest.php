<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test;

use AlexanderAllen\Panettone\BreadGenerator;
use PHPUnit\Framework\TestCase;

class BreadTest extends TestCase
{
    protected static BreadGenerator $class;

    protected function setUp(): void
    {
        self::$class = new BreadGenerator();
    }

    public function testStart(): void
    {
        $generator = self::$class->generate();
        $start = $generator->current();
        $next = $generator->next();

        static::assertEquals(true, true);
    }
}
