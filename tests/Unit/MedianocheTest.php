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

use function Symfony\Component\String\u;

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
     * @param string $spec
     * @param bool $log
     *
     * @return array{OpenApi, Printer}
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

    /**
     * Transform a cebe openapi graph into physical form using nette.
     *
     * goals:
     * all logic must be atomic, encapsulated in units, testable, and composable (functional)
     * cyclomatic comp always lower than 5, always
     * no nested iterations
     * no internal states (OOP this, etc), you get what you give only
     *
     * inspirtion from schemagen, filesgenerator, etc.
     *   propgen.php: per-prop type generator, buggy
     *   FilesGen.php: way too much in one file, mostly CSfixer stuff
     *   openapi/Generator.php: injects nette printer into filesgen
     *   schema/generator.php: same, but with schema.org parsing
     *   class_php::toNetteFile() the big nette implementation, evertying else dances around it
     *
     * 3/18 intermediate assertion/goal/steps
     * usable graph (cebe should be fine)
     * graph processor (generator?) nette implementation for graph
     * dumper
     *
     * @return void
     * @throws TypeErrorException
     * @throws UnresolvableReferenceException
     * @throws IOException
     * @throws Exception
     * @throws ExpectationFailedException
     */
    // #[Test]
    #[TestDox('Dump cebe graph into nette class string')]
    public function cebeToNetteString(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/medianoche.yml');

        $result_user = $spec->components->schemas['User'];
        self::assertContainsOnlyInstancesOf(
            Schema::class,
            $result_user->properties,
            'All references in a schema should be resolved'
        );
        $this->logger->info('All User schema prop references are resolved');
        $this->logger->debug(get_class($result_user->properties['contact_info']));

        // Test first schema only.
        // Transform cebe props to nette props.
        $class = new ClassType('User');
        $schema = $spec->components->schemas['User'];

        foreach ((new MediaNoche())->propertyGenerator($schema) as $name => $nette_prop) {
            self::assertInstanceOf(Property::class, $nette_prop, 'Generator yields Property objects');
            $class->addMember($nette_prop);
        }

        $class
            ->setFinal()
            ->addComment("Class description.\nSecond line\n");

        $printer = new Printer();
        $this->logger->debug($printer->printClass($class));
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

    // ADV_ALLOF_EDGECASE
    // TooManyRequests:
    // allOf:
    //   - $ref: '#/components/schemas/Error'
    //   - $ref: '#/components/schemas/Error'
    //   - type: object
    //     properties:
    //       spam_warning_urn:
    //         type: string
    //       some_other_ref:
    //         $ref: '#/components/schemas/Error'

    /**
     * Test for schema with a Type of "allOf".
     *
     * # This ...
     * TooManyRequests:
     *   allOf:
     *     - $ref: '#/components/schemas/Error'
     *     - type: object
     *       properties:
     *         spam_warning_urn:
     *           type: string
     *
     * # Should translate to this ...
     *   Class TooManyRequests
     *     public readonly ?Error $error = null
     *     public ?string $spam_warning_urn = null
     *
     * Notes:
     *   - cebe interprets the first $ref as an object, complete with inline properties ready for use
     *     when using `RESOLVE_MODE_ALL`. Adding these props straight away would result in inlined props
     *     and therefore duplicate props between types!
     *   - Both the first $ref Schema item and the second 'anonymous' Schema item have a cebe
     *     Schema type of `object`. Keep an eye on the conditions on the prop generator
     *     to prevent these getting mixed up.
     *
     * @TODO Test more complex edge cases w/ multiple and nested references. See ADV_ALLOF_EDGECASE above.
     *
     * @see https://swagger.io/docs/specification/data-models/oneof-anyof-allof-not/
     */
    #[Test]
    #[Depends('proceduralish')]
    #[TestDox('Simple use case for schema of type allOf')]
    public function schemaTypeAllOf(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/allOf-simple.yml');

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
    #[TestDox('Assert unsupported use case for anyOf')]
    public function invalidSchemaTypeAnyOf(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/anyOf-invalid.yml');

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
    #[TestDox('Assert simple use case for anyOf')]
    public function schemaTypeAnyOf(): void
    {
        [$spec, $printer] = $this->realSetup('tests/fixtures/anyOf-simple.yml', true);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = $this->newNetteClass($schema, $name);
            $classes[$name] = $class;
            $this->logger->debug($printer->printClass($class));
        }
    }

    /**
     * What it says on the tin.
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
            // @TODO tests for these use cases:
            // $schema->enum;
            // $schema->uniqueItems;
            // $schema->additionalProperties;
            // $schema->not

            // TooManyRequests is a hard one bc it contains 1. AllOf(ref), 2.add'tProps w/ object.
            // An even more hardcore test would be add'tProps object w/ ref or even more w/ all/anyOfs (refs)

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
            // @TODO This is ripe for conversion into an enum match.
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

    private function newObj(Schema $schema, string $class_name = ''): callable
    {
        $refdSchema = static fn (Schema $schema): string =>
            Collection::fromIterable($schema->items->getDocumentPosition()->getPath())
            ->last('');

        // Set aside nested cebe objects for additional processing.
        // I'd be nice to have an injectable rules matcher/executor for unit testing.
        static $nested_objects = [];
        $that = $this;
        $new_obj = static function (Schema $property, string $propName) use ($refdSchema, $nested_objects, $class_name, $that): Property {

            $nested_objects[$propName] = $property;
            if ($property->type == 'array') {
                // Shape: "object schema, has array property, items have single reference
                // PHP shape: Type -> CustomType $propertyName
                // The type of the property must match the referenced type (class).
                if ($property->items instanceof \cebe\openapi\spec\Schema) {
                    // Use the referenced schema as the type for the property.
                    return $that->nativeProp($property->items, $propName, null, $refdSchema($property), $class_name);
                }
            }

            // This will create a new class property with a custom type.
            return $that->nativeProp($property, $propName, null, null, $class_name);
        };
        return $new_obj;
    }

    /**
     * Converts a property from a cebe to a nette object.
     *
     * @param Schema $property
     * @param string $propName
     * @param null|Collection<Property, string> $collection Present when calling from a `Collection::method()`.
     * @param null|string $typeName
     * @param null|string $class_name
     * @return Property
     * @throws UnhandledMatchError
     * @throws InvalidArgumentException
     */
    public function nativeProp(
        Schema $property,
        string $propName,
        ?Collection $collection = null,
        ?string $typeName = null,
        ?string $class_name = null,
    ): Property {

        $unhandled_type = static fn ($type, $name, $class_name): \UnhandledMatchError =>
        new \UnhandledMatchError(
            sprintf(
                'Unhandled type "%s" for property "%s" of schema "%s"',
                $type,
                $name,
                $class_name
            )
        );

        /**
         * Custom type identifier.
         *
         * The physical type filename and class name must match.
         * Usually the type (schema/class) is capitalized CamelCase,
         * whereas class properties that reference the types are camel_case.
         *
         * @see api-platform/schema-generator/src/AttributeGenerator/GenerateIdentifierNameTrait.php
         */
        $normalizer = static fn ($name) => ucfirst(u($name)->camel()->toString());

        $last = static fn (Schema|Reference $p, ?bool $list = false): string =>
            Collection::fromIterable(
                $list === false ?
                $p->getDocumentPosition()->getPath() :
                $p->items->getDocumentPosition()->getPath()
            )->last('');

        $newProp = (new Property($propName))
            ->setReadOnly(true)
            ->setComment($property->description)
            ->setNullable(true)
            ->setValue($property->default);

        // $property->anyOf[0]->getDocumentPosition()->parent()->getPointer()
        // "/components/schemas"
        $starred = false;
        $starRefs = [];
        foreach (['allOf', 'anyOf', 'oneOf'] as $star) {
            if (
                isset($property->{$star}) &&
                is_array($property->{$star}) &&
                ! empty($property->{$star})
            ) {
                // $property->type "object"
                // @TODO What happens when you have a native + non-native type union? (test case)
                // and what about *Ofs nested deeper inside properties
                // $starRefs[] = $this->propertyGenerator($property, $propName);
                foreach ($property->{$star} as $starRef) {
                    $starRefs[] = $last($starRef);
                }
                $starred = true;
            }
        }

        if ($starred) {
            $newProp->setType(Type::union(...$starRefs));
            return $newProp;
        }

        $newProp->setType(
            /* @see https://swagger.io/specification/#data-types */
            match ($property->type) {
                'string' => 'string',
                'integer' => 'int',
                'boolean' => 'bool',
                'float', 'double' => 'float',
                'object', 'array' => $normalizer($typeName ?? $propName),
                'date', 'dateTime' => \DateTimeInterface::class,
                default => throw $unhandled_type($property->type, $propName, $class_name),
            }
        );
        return $newProp;
    }

    /**
     * Nette class generator.
     *
     * Does two things: generate the class, populate it with properties.
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
         */
        $nette_props = Collection::fromIterable($schema->properties)->ifThenElse(
            $natives,
            [$this, 'nativeProp'],
            [$this, 'nativeProp'],
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
            $prop = $this->nativeProp($schema, 'items', null, $last($schema), $class_name);
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
                    yield $lastRef => $this->nativeProp($property, strtolower($lastRef), null, $lastRef, $class_name);
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
                        yield $key => $this->nativeProp($value, $key);
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
         * @TODO The above comment needs to be worded better, but the ball needs to get rollin'
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
