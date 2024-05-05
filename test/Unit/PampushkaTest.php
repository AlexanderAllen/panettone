<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Test\Setup;
use AlexanderAllen\Panettone\Setup as ParentSetup;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Bread\NetteContainer;
use AlexanderAllen\Panettone\Command\Main;
use PHPUnit\Framework\Attributes\{CoversClass, Group, TestDox, UsesClass};
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
#[UsesClass(NetteContainer::class)]
#[CoversClass(Main::class)]
#[TestDox('Pampushka: Ukranian garlic bread')]
class PampushkaTest extends TestCase
{
    use Setup;

    #[TestDox('Test application command')]
    public function testCommand(): void
    {
        // Statically cache a valid settings location for the command.
        PanDeAgua::getSettings('test/schema/settings-debug.ini');

        $app = new Application('panettone', '0.0.0');
        $app->setAutoExit(false);
        $main = new Main();
        $app->add($main);
        $app->setDefaultCommand($main->getName(), true);
        $input = ['input' => 'test/schema/keyword-enum-simple.yml'];

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
    #[TestDox('Test verbose output options')]
    // #[Group('ignore')]
    public function testVerbose(): void
    {
        PanDeAgua::getSettings("test/schema/settings.ini");

        $app = new Application('panettone', '0.0.0');
        $app->setAutoExit(false);
        $main = new Main();
        $app->add($main);
        $app->setDefaultCommand($main->getName(), true);
        $input = ['input' => 'test/schema/keyword-enum-simple.yml'];

        // For options available see initOutput() in TesterTrait.php.
        // Panettone recognizes only one verbosity setting, so anything above
        // OutputInterface::VERBOSITY_NORMAL will suffice (see Main.php).
        $options = [];
        $options['verbosity'] = OutputInterface::VERBOSITY_VERBOSE;

        $appTester = new ApplicationTester($app);
        $this->assertEquals(Command::SUCCESS, $appTester->run($input, $options));

        // The other options for completeneess sake, gotta collect them all!
        // Commented out b.c. it is a wall of text, but here they are if need be.

        // $this->assertEquals(Command::SUCCESS, $appTester->run(
        //     $input,
        //     ['verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE]
        // ));

        // $this->assertEquals(Command::SUCCESS, $appTester->run(
        //     $input,
        //     ['verbosity' => OutputInterface::VERBOSITY_DEBUG]
        // ));
    }

    /**
     * Coverage for "output" command line option.
     */
    #[TestDox('Test "output" command option')]
    public function testOutputOption(): void
    {
        PanDeAgua::getSettings("test/schema/settings.ini");

        $app = new Application('panettone', '0.0.0');
        $app->setAutoExit(false);
        $main = new Main();
        $app->add($main);
        $app->setDefaultCommand($main->getName(), true);
        $input = [];
        $input['input'] = 'test/schema/keyword-enum-simple.yml';
        $input['output'] = 'tmp';

        $appTester = new ApplicationTester($app);
        $this->assertEquals(Command::SUCCESS, $appTester->run($input, []));
    }
}
