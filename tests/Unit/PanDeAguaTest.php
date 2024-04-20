<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\PanDeAgua;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox};

/**
 * Test suite for file printing.
 *
 * @package AlexanderAllen\Panettone\Test
 * @see https://github.com/AlexanderAllen/panettone/issues/18
 */
#[CoversClass(PanDeAgua::class)]
#[TestDox('PanDeAgua')]
#[Group('nette')]
class PanDeAguaTest extends TestCase
{
}
