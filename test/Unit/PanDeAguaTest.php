<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use AlexanderAllen\Panettone\UnsupportedSchema;
use PHPUnit\Framework\Attributes\{CoversClass, CoversFunction, Group, Test, TestDox, Depends, UsesClass};
use cebe\openapi\{Reader, ReferenceContext, SpecObjectInterface};
use cebe\openapi\spec\{OpenApi, Schema, Reference};
use cebe\openapi\exceptions\{TypeErrorException, UnresolvableReferenceException, IOException};
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;
// use MyCLabs\Enum\Enum as MyCLabsEnum;
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
use Nette\InvalidArgumentException as NetteInvalidArgumentException;

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

    #[Test]
    #[Group('target')]
    #[TestDox('Filesystem test')]
    public function testone(): void
    {
        [$spec, $printer] = $this->realSetup('test/schema/keyword-anyOf-simple.yml', true);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = MediaNoche::newNetteClass($schema, $name);
            $classes[$name] = $class;

            $this->logger->debug($printer->printClass($class));
        }


        foreach ($classes as $name => $class_type) {
            PanDeAgua::printFile($printer, $class_type, 'Foo', 'tmp');
        }

        self::assertTrue(true);
    }
}
