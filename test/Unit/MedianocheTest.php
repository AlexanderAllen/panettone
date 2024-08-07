<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Bread\NetteContainer;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use AlexanderAllen\Panettone\UnsupportedSchema;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox, Depends, UsesClass};
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Type as UtilsType;
use AlexanderAllen\Panettone\Setup as ParentSetup;
use AlexanderAllen\Panettone\Test\Setup;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\Property;

/**
 * Test suite for nette generators.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[CoversClass(MediaNoche::class)]
#[CoversClass(UnsupportedSchema::class)]
#[CoversClass(ParentSetup::class)]
#[CoversClass(NetteContainer::class)]
#[UsesClass(PanDeAgua::class)]
#[TestDox('Medianoche: Fwuffy Cuban sandwich bread')]
#[Group('nette')]
class MedianocheTest extends TestCase
{
    use Setup;

    #[Test]
    #[TestDox('Emit Nette "class like" object(s)')]
    public function cebeToNetteObject(): void
    {
        $settings = PanDeAgua::getSettings('test/schema/settings.ini');
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/medianoche.yml');

        $allowed = [
            ClassType::class,
            EnumType::class,
        ];
        foreach ($classes as $class) {
            $this->assertNotFalse(
                array_search($class::class, $allowed, true),
                'Medianoche emits instances of the correct Nette objects'
            );
        }
    }

    /**
     * Proceduralish class resolver with recursion.
     */
    #[Test]
    #[TestDox('Proceduralish class resolver')]
    public function proceduralish(): void
    {
        [$spec, $printer] = $this->realSetup('test/schema/medianoche-1.yml');
        $settings = PanDeAgua::getSettings("test/schema/settings.ini");

        foreach ($spec->components->schemas as $name => $schema) {
            $container = MediaNoche::newNetteClass($schema, $name, $settings);
            $this->assertInstanceOf(NetteContainer::class, $container);
            $this->assertContainsOnlyInstancesOf(Property::class, $container->props);
            $this->assertInstanceOf(ClassType::class, $container->class, 'Generator yields ClassType object(s)');
        }
    }

    /**
     * Test case for allOf.
     */
    #[Test]
    #[Depends('proceduralish')]
    #[TestDox('Simple use case for keyword allOf')]
    public function schemaTypeAllOf(): void
    {
        $settings = PanDeAgua::getSettings('test/schema/settings.ini');
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/keyword-allOf-simple.yml');

        self::assertCount(
            2,
            $classes['TooManyRequests']->getProperties(),
            'Schemas of type allOf should not inline properties of Referenced objects'
        );

        self::assertEquals(
            'Error',
            $classes['TooManyRequests']->getProperty('error')->getType(),
            'The type on properties that reference other types should match the referenced type'
        );
    }

    #[Test]
    #[TestDox('Assert type intersection for keyword allOf')]
    public function schemaAllOfIntersect(): void
    {
        $settings = PanDeAgua::getSettings('test/schema/settings.ini');
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/keyword-allOf-simple.yml');

        $this->assertArrayHasKey('PanettoneAllOf', $classes, 'Test subject is present');
        $subject = $classes['PanettoneAllOf'];
        $this->assertTrue($subject->hasProperty('origin'), 'Test member is present');
        $member = $subject->getProperty('origin');

        // See https://doc.nette.org/en/utils/type.
        $type = UtilsType::fromString($member->getType());
        $names = $type->getNames();

        $this->assertContains('Me', $names, 'Assert member property references allOf type.');
        $this->assertContains('Error', $names, 'Assert member property references allOf type.');
        $this->assertTrue($type->isIntersection(), 'Assert member property is of type intersection');
    }

    #[Test]
    #[Depends('schemaTypeAllOf')]
    #[TestDox('Assert unsupported use case for keyword anyOf')]
    public function invalidSchemaTypeAnyOf(): void
    {
        [$spec, $printer] = $this->realSetup('test/schema/keyword-anyOf-invalid.yml');
        $settings = PanDeAgua::getSettings("test/schema/settings.ini");

        $this->expectException(UnsupportedSchema::class);
        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = MediaNoche::newNetteClass($schema, $name, $settings);
            $classes[$name] = $class;
            $this->logger->debug($printer->printClass($class));
        }
    }

    #[Test]
    #[Depends('invalidSchemaTypeAnyOf')]
    #[TestDox('Assert type union for keyword anyOf')]
    public function schemaTypeAnyOf(): void
    {
        $settings = PanDeAgua::getSettings('test/schema/settings.ini');
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/keyword-anyOf-simple.yml');

        $this->assertArrayHasKey('PanettoneAnyOf', $classes, 'Test subject is present');
        $subject = $classes['PanettoneAnyOf'];
        $this->assertTrue($subject->hasProperty('origin'), 'Test member is present');
        $member = $classes['PanettoneAnyOf']->getProperty('origin');

        // See https://doc.nette.org/en/utils/type.
        $type = UtilsType::fromString($member->getType());
        $names = $type->getNames();

        $this->assertContains('Me', $names, 'Assert member property references anyOf type.');
        $this->assertContains('User', $names, 'Assert member property references anyOf type.');
        $this->assertTrue($type->isUnion(), 'Assert member property is of type union');
    }

    #[Test]
    #[TestDox('Assert use case for keyword oneOf')]
    public function schemaTypeOneOf(): void
    {
        $settings = PanDeAgua::getSettings('test/schema/settings.ini');
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/keyword-oneOf-simple.yml');

        $this->assertArrayHasKey('TestSubject', $classes, 'Test subject is present');
        $subject = $classes['TestSubject'];
        $this->assertTrue($subject->hasProperty('origin'), 'Test member is present');
        $member = $subject->getProperty('origin');

        // See https://doc.nette.org/en/utils/type.
        $type = UtilsType::fromString($member->getType());
        $names = $type->getNames();

        $this->assertContains('Me', $names, 'Assert member property references *Of type.');
        $this->assertContains('User', $names, 'Assert member property references *Of type.');
        $this->assertTrue($type->isUnion(), 'Assert member property type is a union');
    }

    /**
     * Use case for `not` keyword.
     *
     * There is no such thing as a negation type, from a static code perspective.
     * From the [PetStore](https://swagger.io/docs/specification/data-models/oneof-anyof-allof-not/) documentation:
     *
     * ```text
     * In this example, user should specify the pet_type value of any type except integer...
     * ```
     * The closest thing I think can match this requirement is the `mixed` type,
     * which ironically is the default in a loose-type language like PHP. However, since PHP 8.0
     * `mixed` can be specified literally, which would make the printed type's intent more clear.
     *
     * Mixed references:
     *  - [PHP Manual, types](https://www.php.net/manual/en/language.types.mixed.php)
     *  - [PHP 8.0: New mixed pseudo type](https://php.watch/versions/8.0/mixed-type)
     *
     * Mixed is kinda evil and you shouldn't use it, but I do need to have at least some sort of
     * basic detection/support for the use case so the program doesn't explode. This use case is
     * for testing that basic support using `mixed`.
     */
    #[Test]
    #[TestDox('Assert use case for keyword not')]
    public function schemaTypeNot(): void
    {
        $settings = PanDeAgua::getSettings('test/schema/settings.ini');
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/keyword-not-simple.yml');

        $this->assertArrayHasKey('TestSubject', $classes, 'Test subject is present');
        $subject = $classes['TestSubject'];
        $this->assertTrue($subject->hasProperty('property_scalar'), 'Test property is present');
        $member = $subject->getProperty('property_scalar');

        // See https://doc.nette.org/en/utils/type.
        $type = UtilsType::fromString($member->getType());
        $names = $type->getNames();

        $this->assertContains('mixed', $names, 'Assert member property is of type mixed.');
        $this->assertTrue($type->isSimple() && $type->isBuiltin(), 'Assert member property type.');
    }

    /**
     * Test case for enumerated properties.
     *
     * If an enum has been detected, the property will reference an Enum object,
     * and therefore should follow object naming conventions. Both property and type
     * names are camel case, but the type is capitalized and the prop name is not.
     */
    #[Test]
    #[TestDox('Assert use case for enumerations')]
    public function schemaEnum(): void
    {
        $settings = PanDeAgua::getSettings('test/schema/settings-debug.ini');
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/keyword-enum-simple.yml');

        $this->assertArrayHasKey('PanettoneEnum', $classes, 'Test subject is present');
        $subject = $classes['PanettoneEnum'];
        $this->assertTrue($subject->hasProperty('enum_pastries'), 'Test property is present');
        $member = $subject->getProperty('enum_pastries');

        $type = UtilsType::fromString($member->getType());

        $this->assertEquals(
            'Enum_pastries',
            $type->getSingleName(),
            'Assert member property references another object type.'
        );
    }

    public function testPropertyTypeArray(): void
    {
        $settings = PanDeAgua::getSettings('test/schema/settings-debug.ini');
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/property-array.yml');

        $this->assertArrayHasKey('TestCase', $classes, 'Test subject is present');
        $subject = $classes['TestCase'];
        $this->assertTrue($subject->hasProperty('collection'), 'Test property is present');
        $member = $subject->getProperty('collection');

        $type = UtilsType::fromString($member->getType());
        $this->assertEquals(
            'array',
            $member->getType(),
            'Assert member property type.'
        );
    }

    #[Group('target')]
    public function testFunctional(): void
    {
        $settings = PanDeAgua::getSettings('test/schema/settings-debug.ini');
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/collection-simple.yml');

        $this->assertArrayHasKey('TestCase', $classes, 'Test subject is present');
        $subject = $classes['TestCase'];
        $this->assertTrue($subject->hasProperty('collection'), 'Test property is present');
        $member = $subject->getProperty('collection');

        // $type = UtilsType::fromString($member->getType());
        // $this->assertEquals(
        //     'array',
        //     $member->getType(),
        //     'Assert member property type.'
        // );
    }

    #[TestDox('Assert nullable and default settings')]
    public function testNullableDefault(): void
    {
        $settings = parse_ini_file('test/schema/settings-nullable.ini', true, INI_SCANNER_TYPED);
        $classes = (new MediaNoche())->sourceSchema($settings, 'test/schema/keyword-allOf-simple.yml');

        foreach ($classes as $class) {
            if ($class instanceof ClassType) {
                foreach ($class->getProperties() as $prop) {
                    $this->assertTrue($prop->isInitialized(), 'Property has default value assinged');
                    $this->assertTrue($prop->isNullable(), 'Property is set as nullable');
                }
            }
        }
    }

    /**
     * Tests verbose logging is activated via the user configuration file.
     *
     * Ojo: This test is a bit mouthy so you might want to add to the ignore group.
     * Note that supressing this test might reduce coverage by a small percent.
     */
    // #[Group('ignore')]
    #[TestDox('Test "debug" setting in configuration file')]
    public function testDebugSetting(): void
    {
        $settings = parse_ini_file('test/schema/settings-debug.ini', true, INI_SCANNER_TYPED);
        $instance = new MediaNoche();
        $instance->sourceSchema($settings, 'test/schema/keyword-allOf-simple.yml');

        $this->assertEquals(
            'Symfony\Component\Console\Logger\ConsoleLogger',
            $instance->getLoggerClass(),
            'Assert logging is turned on'
        );

        $settings = parse_ini_file('test/schema/settings-nullable.ini', true, INI_SCANNER_TYPED);
        $instance = new MediaNoche();
        $instance->sourceSchema($settings, 'test/schema/keyword-allOf-simple.yml');

        $this->assertEquals(
            'Psr\Log\NullLogger',
            $instance->getLoggerClass(),
            'Assert logging is turned off'
        );
    }
}
