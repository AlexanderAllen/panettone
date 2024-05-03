<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Test\Setup;
use AlexanderAllen\Panettone\Setup as ParentSetup;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Command\Main;
use PHPUnit\Framework\Attributes\{CoversClass, TestDox, UsesClass};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test for command line application.
 *
 * @package AlexanderAllen\Panettone\Test
 * @see https://github.com/AlexanderAllen/panettone/issues/17
 *
 * Initial test example inspired by, but replaced by ApplicationTester.
 * @see https://github.com/api-platform/schema-generator/blob/997f6f811faa75006aeff72cec26fe291bb8eaab/tests/Command/GenerateCommandTest.php
 */
#[CoversClass(PanDeAgua::class)]
#[UsesClass(MediaNoche::class)]
#[UsesClass(ParentSetup::class)]
#[CoversClass(Main::class)]
#[TestDox('Pampushka')]
class PampushkaTest extends TestCase
{
    use Setup;

    #[TestDox('Test command')]
    public function testCommand(): void
    {
        // Statically cache a valid settings location for the command.
        PanDeAgua::getSettings("test/schema/settings.ini");

        $app = new Application('panettone', '0.0.0');
        $app->setAutoExit(false);
        $main = new Main();
        $app->add($main);
        $app->setDefaultCommand($main->getName(), true);
        $input = ['input' => 'test/schema/keyword-anyOf-simple.yml'];

        $appTester = new ApplicationTester($app);
        $this->assertEquals(Command::SUCCESS, $appTester->run($input, []));
    }

    #[TestDox('Assert bad source results in command failure')]
    public function testCommandFail(): void
    {
        $input = ['input' => 'test/schema/bad-source.yml'];
        $commandTester = new CommandTester(new Main());
        $this->assertEquals(1, $commandTester->execute($input, []));
    }

    /**
     * Test coverage for application verbosity.
     *
     * For internal usage of verbosity, see links below.
     * @see vendor/symfony/console/Tester/ApplicationTester.php
     * @see vendor/symfony/console/Tester/TesterTrait.php.
     */
    #[TestDox('Test verbose output')]
    public function testVerbose(): void
    {
        PanDeAgua::getSettings("test/schema/settings.ini");

        $app = new Application('panettone', '0.0.0');
        $app->setAutoExit(false);
        $main = new Main();
        $app->add($main);
        $app->setDefaultCommand($main->getName(), true);
        $input = ['input' => 'test/schema/keyword-anyOf-simple.yml'];

        // For options available see initOutput() in TesterTrait.php.
        $options = [];
        $options['verbosity'] = OutputInterface::VERBOSITY_VERBOSE;

        $appTester = new ApplicationTester($app);
        $this->assertEquals(Command::SUCCESS, $appTester->run($input, $options));
    }
}
