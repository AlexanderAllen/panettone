<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test;

use Psr\Log\NullLogger;
use AlexanderAllen\Panettone\Setup as ParentSetup;

/**
 * Common setup trait for unit tests.
 *
 * @package AlexanderAllen\Panettone\Test
 * @see https://github.com/AlexanderAllen/panettone/issues/18
 */
trait Setup
{
    use ParentSetup;

    protected function setUp(): void
    {
        $this->setLogger(new NullLogger());
    }
}
