<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Test\Setup;
use AlexanderAllen\Panettone\Setup as ParentSetup;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Command\Main;
use PHPUnit\Framework\Attributes\{CoversClass, Group, TestDox, UsesClass};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test for command line application.
 *
 * @package AlexanderAllen\Panettone\Test
 * @see https://github.com/AlexanderAllen/panettone/issues/17
 *
 * For a really good CLI test example,
 * @see https://github.com/api-platform/schema-generator/blob/997f6f811faa75006aeff72cec26fe291bb8eaab/tests/Command/GenerateCommandTest.php
 */
#[UsesClass(PanDeAgua::class)]
#[UsesClass(MediaNoche::class)]
#[UsesClass(ParentSetup::class)]
#[CoversClass(Main::class)]
#[TestDox('Pampushka')]
class PampushkaTest extends TestCase
{
    use Setup;

    #[Group('target')]
    #[TestDox('Test command')]
    public function testCommand(): void
    {
        // Statically cache a valid settings location for the command.
        PanDeAgua::getSettings("test/schema/settings.ini");

        $input = ['source' => 'test/schema/keyword-anyOf-simple.yml'];
        $commandTester = new CommandTester(new Main());
        $this->assertEquals(0, $commandTester->execute($input, []));
    }

    #[Group('target')]
    #[TestDox('Assert bad source results in command failure')]
    public function testCommandFail(): void
    {
        $input = ['source' => 'test/schema/bad-source.yml'];
        $commandTester = new CommandTester(new Main());
        $this->assertEquals(1, $commandTester->execute($input, []));
    }
}
