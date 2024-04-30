<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Test\Setup;
use AlexanderAllen\Panettone\Setup as ParentSetup;
use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use AlexanderAllen\Panettone\Command\Main;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox, UsesClass};
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use Nette\PhpGenerator\Type;
use Nette\Utils\Type as UtilsType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

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

    /**
     * Test for configuration files.
     *
     * Things that I need in settings:
     * - Type output path, currently hardcoded usually to tmp.
     * - Namespace to be used for generated types.
     *
     * @return void
     */
    #[Group('target')]
    #[TestDox('Test command')]
    public function testCommand(): void
    {
        $settings = PanDeAgua::getSettings("test/schema/settings.ini");
        $output_path = $settings['file']['output_path'];
        $namespace = $settings['file']['namespace'];
        [$spec, $printer] = $this->realSetup('test/schema/keyword-anyOf-simple.yml', true);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = MediaNoche::newNetteClass($schema, $name);
            $classes[$name] = $class;
            // $this->logger->debug($printer->printClass($class));
        }

        foreach ($classes as $name => $class_type) {
            PanDeAgua::printFile($printer, $class_type, $settings);

            $target = sprintf('%s/%s.php', $output_path, $name);
            $file = PhpFile::fromCode(file_get_contents($target));

            // Testing for existance of single namespace.
            $namespaces = $file->getNamespaces();
            self::assertArrayHasKey($namespace, $namespaces, 'Generated file contains specified namespace');
        }
    }
}
