<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\UnsupportedSchema;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversFunction, Group, Test, TestDox, Depends};
use Psr\Log\{LoggerAwareTrait, NullLogger};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use cebe\openapi\{Reader, ReferenceContext, SpecObjectInterface};
use cebe\openapi\spec\{OpenApi, Schema, Reference};
use cebe\openapi\exceptions\{TypeErrorException, UnresolvableReferenceException, IOException};
use cebe\openapi\json\JsonPointer;
use Generator;
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
use loophp\collection\Collection;
use UnhandledMatchError;
use Nette\InvalidArgumentException;
use Nette\PhpGenerator\Type;
use Nette\Utils\Type as UtilsType;

/**
 * Test suite for nette generators.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[CoversClass(MediaNoche::class)]
#[CoversClass(UnsupportedSchema::class)]
#[TestDox('Medianoche')]
#[Group('nette')]
class MedianocheTest extends TestCase
{
    use LoggerAwareTrait;

    protected static \Generator $generator;

    protected function setUp(): void
    {
        self::setLogger(new NullLogger());
    }

    /**
     * The real fixture method - setup the spec and logging for every test.
     *
     * Most tests in this suite read from a OAS source. This method just cuts
     * down some of that boilerplate, along with some of the logging ceremonies.
     *
     * @param string $spec
     *   The path to the Open API specification.
     * @param bool $log
     *   A Nette Printer instance used for logging and debugging.
     *
     * @return array{OpenApi, Printer}
     *   A tuple with the cebe OAS graph a Nette Printer instance.
     * @throws TypeErrorException
     * @throws UnresolvableReferenceException
     * @throws IOException
     */
    public function realSetup(string $spec, bool $log = false): array
    {
        $this->setLogger($log ?
            new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG)) :
            new NullLogger());

        // if (function_exists('xdebug_break') && $log === true) {
        //     xdebug_break();
        // }

        return [
            Reader::readFromYamlFile(
                realpath($spec),
                OpenAPI::class,
                ReferenceContext::RESOLVE_MODE_ALL,
            ),
            new Printer()
        ];
    }

    #[Test]
    #[TestDox('Create nette class object(s)')]
    public function cebeToNetteObject(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/medianoche.yml');

        $classes = [];
        $expected_count = count($spec->components->schemas);
        foreach ($spec->components->schemas as $name => $schema) {
            $class = $this->newNetteClass($schema, $name);
            self::assertInstanceOf(ClassType::class, $class, 'Generator yields ClassType object(s)');
            $classes[] = $class;
        }

        self::assertCount(
            $expected_count,
            $classes,
            'The given and yielded object amount is an exact match'
        );
    }

    /**
     * Proceduralish class resolver with recursion.
     */
    #[Test]
    #[TestDox('Proceduralish class resolver')]
    public function proceduralish(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/medianoche-1.yml');

        $classes = [];
        $expected_count = count($spec->components->schemas);
        foreach ($spec->components->schemas as $name => $schema) {
            $class = $this->newNetteClass($schema, $name);
            self::assertInstanceOf(ClassType::class, $class, 'Generator yields ClassType object(s)');
            $classes[] = $class;
            $this->logger->debug($printer->printClass($class));
        }

        self::assertCount(
            $expected_count,
            $classes,
            'The given and yielded object amount is an exact match'
        );
    }

    /**
     * Test case for allOf.
     *
     * @TODO Update assertions, see issue #19.
     */
    #[Test]
    #[Depends('proceduralish')]
    #[TestDox('Simple use case for keyword allOf')]
    public function schemaTypeAllOf(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/keyword-allOf-simple.yml', true);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = $this->newNetteClass($schema, $name);
            $classes[$name] = $class;
            $this->logger->debug($printer->printClass($class));
        }

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
    #[Depends('schemaTypeAllOf')]
    #[TestDox('Assert unsupported use case for keyword anyOf')]
    public function invalidSchemaTypeAnyOf(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/keyword-anyOf-invalid.yml');

        $this->expectException(UnsupportedSchema::class);
        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = $this->newNetteClass($schema, $name);
            $classes[$name] = $class;
            $this->logger->debug($printer->printClass($class));
        }
    }

    #[Test]
    #[Depends('invalidSchemaTypeAnyOf')]
    #[TestDox('Assert union use case for keyword anyOf')]
    public function schemaTypeAnyOf(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/keyword-anyOf-simple.yml');

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = $this->newNetteClass($schema, $name);
            $classes[$name] = $class;
            $this->logger->debug($printer->printClass($class));
        }

        $this->assertArrayHasKey('PanettoneAnyOf', $classes, 'Test subject is present');
        $subject = $classes['PanettoneAnyOf'];
        $this->assertTrue($subject->hasProperty('origin'), 'Test member is present');
        $member = $classes['PanettoneAnyOf']->getProperty('origin');

        // See https://doc.nette.org/en/utils/type.
        $type = UtilsType::fromString($member->getType());
        $names = $type->getNames();

        $this->assertContains('Me', $names, 'Assert member property references anyOf type.');
        $this->assertContains('User', $names, 'Assert member property references anyOf type.');
        $this->assertTrue($type->isUnion(), 'Assert member property type is a union');
    }

    #[Test]
    #[TestDox('Assert use case for keyword oneOf')]
    public function schemaTypeOneOf(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/keyword-oneOf-simple.yml', true);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = $this->newNetteClass($schema, $name);
            $classes[$name] = $class;
            $this->logger->debug($printer->printClass($class));
        }

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
    #[Group('target')]
    #[TestDox('Assert use case for keyword not')]
    public function schemaTypeNot(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/keyword-not-simple.yml', true);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = $this->newNetteClass($schema, $name);
            $classes[$name] = $class;
            $this->logger->debug($printer->printClass($class));
        }

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
     * What it says on the tin.
     *
     * @TODO Test cases for
     * - $schema->enum: Required by issue #20
     * - Other use cases listed in issue #21
     *
     * @param Schema $schema
     * @param string $class_name
     * @return string The type of Schema.
     */
    private function typeMatcher2000(Schema $schema, string $class_name): string
    {
        $unhandled_class = static fn ($type, $class_name): \UnhandledMatchError =>
            new \UnhandledMatchError(
                sprintf(
                    'Unhandled type "%s" for schema "%s"',
                    $type,
                    $class_name
                )
            );

        $adv_types = ['allOf', 'anyOf', 'oneOf'];
        $advanced = static function (Schema $schema) use ($unhandled_class, $class_name, $adv_types): string {

            //  Could you have a schema with multiple of these?
            $kind = Collection::fromIterable($adv_types)
                ->reject(static fn ($v) => is_null($schema->{$v}))
                ->first('');
            if (empty($kind)) {
                // Out of schema types to match, time to throw an exception.
                throw $unhandled_class('unknown', $class_name);
            }
            return $kind;
        };

        $schemaType = (static fn ($schema): string =>
            match ($schema->type) {
                /* @see https://swagger.io/specification/#data-types */
                'string' => 'string',
                'integer' => 'int',
                'boolean' => 'bool',
                'float', 'double' => 'float',
                'object' => 'object',
                'array' => 'array',
                'date', 'dateTime' => \DateTimeInterface::class,
                default => $advanced($schema),
            })($schema);

        return $schemaType;
    }

    /**
     * Nette class generator.
     *
     * Does two things: generate the class, populate it with properties.
     *
     * @TODO Issues #22, #23, namespaces and config file.
     */
    private function newNetteClass(Schema $schema, string $class_name): ClassType
    {
        $schemaType = $this->typeMatcher2000($schema, $class_name);

        $this->logger->debug(sprintf('Creating new class for "%s" schema "%s"', $schemaType, $class_name));
        $class = new ClassType(
            $class_name,
            (new PhpNamespace('DeyFancyFooNameSpace'))
                ->addUse('UseThisUseStmt', 'asAlias')
        );

        $props = $this->propertyGenerator($schema, $class_name);
        foreach ($props as $key => $value) {
            $class->addMember($value);
        }

        return $class;
    }

    /**
     * Converts all the properties from a cebe Schema into nette Properties.
     *
     * @param Schema $schema
     * @param string $class_name
     * @return array<Property>
     * @throws UnhandledMatchError
     * @throws InvalidArgumentException
     */
    public function propertyGenerator(Schema $schema, string $class_name): array
    {
        $__props = [];

        $last = static fn (Schema|Reference $p, ?bool $list = false): string =>
            Collection::fromIterable(
                $list === false ?
                $p->getDocumentPosition()->getPath() :
                $p->items->getDocumentPosition()->getPath()
            )->last('');

        // Native type parsing.
        $natives = static fn ($p) => ! in_array($p->type, ['object', 'array'], true);

        /**
         * Convert all cebe schema props to nette props.
         * @var Collection<string, Property> $nette_props
         *
         * @TODO Cleanup per tix #15.
         */
        $nette_props = Collection::fromIterable($schema->properties)->ifThenElse(
            $natives,
            [MediaNoche::class, 'nativeProp'],
            [MediaNoche::class, 'nativeProp'],
        );
        foreach ($nette_props as $name => $prop) {
            $this->logger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
            $__props[$name] = $prop;
        }

        // Schema has type array.
        // Shape: "array schema, items point to single ref"
        if ($schema->type === 'array') {
            // Don't flatten or inline the reference, instead reference the schema as a type.
            $this->logger->debug(sprintf('[%s/%s] Add array class property', $class_name, 'items'));
            $prop = MediaNoche::nativeProp($schema, 'items', null, $last($schema), $class_name);
            $__props[] = $prop;
        }

        /**
         * Generator maps cebe Schemas to nette Properties.
         *
         * @param list<Schema|Reference> $array
         *
         * @return Generator<mixed, Property, null, void>
         */
        $compositeGenerator = function ($array) use ($class_name, $last): Generator {
            foreach ($array as $key => $property) {
                $lastRef = $last($property);

                // Pointer path with string ending is a reference to another schema.
                if (! is_numeric($lastRef)) {
                    yield $lastRef => MediaNoche::nativeProp($property, strtolower($lastRef), null, $lastRef, $class_name);
                }

                // Pointer path with numerical ending is an internal property.
                if (
                    $property->type === 'object'
                    && is_numeric($lastRef)
                    && isset($property->properties)
                    && !empty($property->properties)
                ) {
                    // The generator steps through all the object properties, causing them to become "inline", or part
                    // of the generated type.
                    foreach ($property->properties as $key => $value) {
                        yield $key => MediaNoche::nativeProp($value, $key);
                    }
                }
            }
        };

        /**
         * Detect an unsuported use case instance.
         *
         * If a starOf is detected in a schema item whose parent is components/schemas
         * it means it's a top-level starOf schema. While this use case is valid OAS YAML,
         * it represents a use case I'm not supporting.
         */
        $starGuard = function (Schema $schema, string $star) use ($class_name) {
            if ('/components/schemas' == $schema->getDocumentPosition()->parent()->getPointer()) {
                throw new UnsupportedSchema(
                    $schema,
                    $class_name,
                    sprintf('Using %s on a top-level schema component', $star)
                );
            }
        };

        if ($schema->allOf) {
            foreach ($compositeGenerator($schema->allOf) as $name => $prop) {
                $this->logger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
                $__props[] = $prop;
            }
        }

        /**
         * anyOf schemas:
         * - Generate every type, regardless if it's reference or inline native/object type.
         * - For each schema reference, generate only the type reference, not the type itself.
         * - For schema reference, add type ref to a union of types.
         * - Inline objects and natives are generated inline.
         * - Inline objects are also part of a union, which capture every single type
         *   mentioned in the anyOf.
         *
         * So basically a anyOf generator should return a big ol list of types to be added to a Union.
         * Some of them just references, some of them fully populated objects or native types.
         *
         * @TODO The commen above and code needs some sanity check per #15.
         *
         * @see https://dev.to/drupalista/dev-log-330-anyof-2jgm
         */
        if ($schema->anyOf) {
            $starGuard($schema, 'anyOf');

            /** @var Property $prop */
            foreach ($compositeGenerator($schema->anyOf) as $name => $prop) {
                $this->logger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
                $__props[] = $prop;
            }
            // $prop->setType()
        }

        return $__props;
    }
}
