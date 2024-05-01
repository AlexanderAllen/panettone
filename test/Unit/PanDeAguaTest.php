<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use PHPUnit\Framework\Attributes\{CoversClass, Group, TestDox, UsesClass};
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\TestCase;
use AlexanderAllen\Panettone\Setup as ParentSetup;
use AlexanderAllen\Panettone\Test\Setup;

/**
 * Test suite for file printing.
 *
 * @package AlexanderAllen\Panettone\Test
 * @see https://github.com/AlexanderAllen/panettone/issues/18
 * @see vendor/api-platform/schema-generator/src/Schema/Generator.php
 * @see vendor/api-platform/schema-generator/src/FilesGenerator.php
 */
#[CoversClass(PanDeAgua::class)]
#[UsesClass(MediaNoche::class)]
#[UsesClass(ParentSetup::class)]
#[TestDox('Pan de agua')]
class PanDeAguaTest extends TestCase
{
    use Setup;

    // #[Group('target')]
    #[TestDox('File printer test')]
    public function testFilePrinter(): void
    {
        $settings = PanDeAgua::getSettings("test/schema/settings.ini");
        [$spec, $printer] = $this->realSetup('test/schema/keyword-anyOf-simple.yml', false);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = MediaNoche::newNetteClass($schema, $name, $settings);
            $classes[$name] = $class;

            $this->logger->debug($printer->printClass($class));
        }

        foreach ($classes as $name => $class_type) {
            PanDeAgua::printFile($printer, $class_type, $settings);

            $target = sprintf('%s/%s.php', $settings['file']['output_path'], $name);
            $file = PhpFile::fromCode(file_get_contents($target));

            foreach ($file->getClasses() as $readName => $readClass) {
                self::assertTrue($name === $readClass->getName(), 'Printed file contains source class');
            };
        }
    }

    /**
     * Test for configuration files.
     *
     * @return void
     */
    // #[Group('target')]
    #[TestDox('Source INI configuration file')]
    public function testIniLoading(): void
    {
        $settings = PanDeAgua::getSettings("test/schema/settings.ini");
        $output_path = $settings['file']['output_path'];
        $namespace = $settings['file']['namespace'];

        [$spec, $printer] = $this->realSetup('test/schema/keyword-anyOf-simple.yml', false);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = MediaNoche::newNetteClass($schema, $name, $settings);
            $classes[$name] = $class;
            // $this->logger->debug($printer->printClass($class));
        }

        foreach ($classes as $name => $class_type) {
            PanDeAgua::printFile($printer, $class_type, $settings);

            $target = sprintf('%s/%s.php', $output_path, $name);
            $file = PhpFile::fromCode(file_get_contents($target));

            // Testing for existence of single namespace.
            $namespaces = $file->getNamespaces();
            self::assertArrayHasKey($namespace, $namespaces, 'Generated file contains specified namespace');
        }
    }
}
