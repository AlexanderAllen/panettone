<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
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
#[TestDox('PanDeAgua')]
class PanDeAguaTest extends TestCase
{
    use Setup;

    #[Group('target')]
    #[TestDox('File printer test')]
    public function testFilePrinter(): void
    {
        [$spec, $printer] = $this->realSetup('test/schema/keyword-anyOf-simple.yml', true);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = MediaNoche::newNetteClass($schema, $name);
            $classes[$name] = $class;

            $this->logger->debug($printer->printClass($class));
        }

        foreach ($classes as $name => $class_type) {
            $path = 'tmp';
            PanDeAgua::printFile($printer, $class_type, 'Foo', $path);

            $target = sprintf('%s/%s.php', $path, $name);
            $file = PhpFile::fromCode(file_get_contents($target));

            foreach ($file->getClasses() as $readName => $readClass) {
                self::assertTrue($name === $readClass->getName(), 'Printed file contains source class');
            };
        }
    }
}
