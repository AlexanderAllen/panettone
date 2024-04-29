<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Test\Setup;
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

/**
 * Test configuration file, using .ini format.
 *
 * @package AlexanderAllen\Panettone\Test
 * @see https://github.com/AlexanderAllen/panettone/issues/22
 */
#[CoversClass(PanDeAgua::class)]
#[UsesClass(MediaNoche::class)]
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
    #[TestDox('Source INI configuration file')]
    public function testLoading(): void
    {
        $settings = parse_ini_file("test/schema/settings.ini", true, INI_SCANNER_TYPED);
        $output_path = $settings['global']['output_path'];
        $namespace = $settings['global']['namespace'];

        [$spec, $printer] = $this->realSetup('test/schema/keyword-anyOf-simple.yml', true);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = MediaNoche::newNetteClass($schema, $name);
            $classes[$name] = $class;
            // $this->logger->debug($printer->printClass($class));
        }

        foreach ($classes as $name => $class_type) {
            PanDeAgua::printFile($printer, $class_type, $namespace, $output_path);

            $target = sprintf('%s/%s.php', $output_path, $name);
            $file = PhpFile::fromCode(file_get_contents($target));

            foreach ($file->getClasses() as $readName => $readClass) {
                self::assertTrue($name === $readClass->getName(), 'Generated file contains source class');
            };

            // Testing for existance of single namespace.
            $namespaces = $file->getNamespaces();
            self::assertArrayHasKey($namespace, $namespaces, 'Generated file contains specified namespace');
        }
    }
}
