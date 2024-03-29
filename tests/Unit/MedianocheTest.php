<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox, Depends};
use Psr\Log\{LoggerAwareTrait, NullLogger};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use cebe\openapi\{Reader, ReferenceContext};
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
use loophp\collection\Operation\Nullsy;

use function Symfony\Component\String\u;

/**
 * Test suite for nette generators.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[CoversClass(MediaNoche::class)]
#[TestDox('Nette tests')]
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
     *   class_php::toNetteFile() the big nette implementation, evertying else dances around it./.......................;ooooooooi9'''9m,9(JNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNMK)
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
        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/medianoche.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL,
        );

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
        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/medianoche.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL,
        );

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
        $logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
        self::setLogger($logger);
        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/medianoche-1.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL,
        );
        $printer = new Printer();

        $callback = fn ($k = null, $v = null) => $this->logger->debug(sprintf('%s: ima call u back, %s', $k, $v));

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
     * Functionalish class resolver without recursion.
     *
     * I've been aiming to avoid procedural recursion at all costs.
     * What if the upper scope / stack member becomes a collection and receive
     * a bucket of nested classes / types to be generated?s
     */
    // #[Test]
    #[TestDox('Functionalish class resolver')]
    public function functionalish(): void
    {
        // ...
    }

    /**
     * Test for schema with a Type of "allOf".
     *
     * @see https://swagger.io/docs/specification/data-models/oneof-anyof-allof-not/
     */
    # #[Test]
    #[TestDox('Schema of type allOf w/ a single ref item')]
    public function schemaTypeAllOf(): void
    {
        // ...
    }

    public function newNetteClass(Schema $schema, string $class_name, callable $callback = null): ClassType
    {
        $unhandled_type = static fn ($type, $name, $class_name): \UnhandledMatchError =>
            new \UnhandledMatchError(
                sprintf(
                    'Unhandled type "%s" for property "%s" of schema "%s"',
                    $type,
                    $name,
                    $class_name
                )
            );

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

        // The root schema type.
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

        $this->logger->debug(sprintf('Creating new class for "%s" schema "%s"', $schemaType, $class_name));
        $class = new ClassType(
            $class_name,
            (new PhpNamespace('DeyFancyFooNameSpace'))
                ->addUse('UseThisUseStmt', 'asAlias')
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

        $_native_prop = static fn (
            Schema $property,
            string $propName,
            ?Collection $collection = null,
            ?string $typeName = null
            ): Property =>
            (new Property($propName))
                ->setReadOnly(true)
                ->setComment($property->description)
                ->setNullable(true)
                ->setValue($property->default)
                ->setType(
                    match ($property->type) {
                        /* @see https://swagger.io/specification/#data-types */
                        'string' => 'string',
                        'integer' => 'int',
                        'boolean' => 'bool',
                        'float', 'double' => 'float',
                        'object', 'array' => $normalizer($typeName ?? $propName),
                        'date', 'dateTime' => \DateTimeInterface::class,
                        default => throw $unhandled_type($property->type, $propName, $class_name),
                    }
                );
        ;

        $refdSchema = static fn (Schema $schema): string =>
            Collection::fromIterable($schema->items->getDocumentPosition()->getPath())
            ->last('');

        // Set aside nested cebe objects for additional processing.
        // I'd be nice to have an injectable rules matcher/executor for unit testing.
        static $nested_objects = [];
        $new_obj = static function (Schema $property, string $propName, ?Collection $collection = null) use ($_native_prop, $refdSchema, $nested_objects): Property {

            $nested_objects[$propName] = $property;
            if ($property->type == 'array') {
                // Shape: "object schema, has array property, items have single reference
                // PHP shape: Type -> CustomType $propertyName
                // The type of the property must match the referenced type (class).
                if ($property->items instanceof \cebe\openapi\spec\Schema) {
                    // Use the referenced schema as the type for the property.
                    return $_native_prop($property->items, $propName, null, $refdSchema($property));
                }
            }

            // This will create a new class property with a custom type.
            return $_native_prop($property, $propName);
        };

        /**
         * Advanced type parsing.
         * I'd be cool to have an engine that can dictate on the fly how to interpret *Ofs.
         * Maybe by breaking the *Of logic into a swappable callable.
         * This might be a good point for recursion since the reference could be referencing anything adf asdasd
         *
         * For example
         * allOf could be interpreted as a merge op
         */
        $starOfs = static fn ($schema) =>
            (
                in_array($schemaType, $adv_types, true) &&
                property_exists($schema, $schemaType) &&
                is_array($schema->{$schemaType})
            )
            ? $schema->{$schemaType}
            : [];
            $test = null;

        // Cat:     # "Cat" is a value for the pet_type property (the discriminator value)
        // allOf: # Combines the main `Pet` schema with `Cat`-specific properties
        //   - $ref: '#/components/schemas/Pet'
        //   - type: object
        //     # all other properties specific to a `Cat`
        //     properties:
        //       hunts:
        //         type: boolean
        //       age:
        //         type: integer

        // TooManyRequests:
        // allOf:
        //   - $ref: '#/components/schemas/Error'
        //   - type: object
        //     properties:
        //       spam_warning_urn:
        //         type: string

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

        // Class TooManyRequests
        //  // Should allOfs be referenced or inlined?
        //  public readonly ?Error $error = null
        //  public ?string $spam_warning_urn = null
        //
        //


        // Native type parsing.
        $natives = static fn ($p) => ! in_array($p->type, ['object', 'array'], true);

        /**
         * Convert all schema props to cebe props.
         * @var Collection<string, Property> $nette_props
         */
        $nette_props = Collection::fromIterable($schema->properties)->ifThenElse($natives, $_native_prop, $new_obj);
        foreach ($nette_props as $name => $prop) {
            $this->logger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
            $class->addMember($prop);
        }

        // Schema has type array.
        // Shape: "array schema, items point to single ref"
        if ($schema->type === 'array') {
            // Don't flatten or inline the reference, instead reference the schema as a type.
            $this->logger->debug(sprintf('[%s/%s] Add array class property', $class_name, 'items'));
            $prop = $_native_prop($schema, 'items', null, $refdSchema($schema));
            $class->addMember($prop);
        }

        $last = fn (Schema $p): string =>
            Collection::fromIterable($p->getDocumentPosition()->getPath())->last('');

        $compositeGenerator = function ($array) use ($new_obj, $last, $_native_prop): Generator {
            foreach ($array as $key => $property) {
                $lastRef = $last($property);

                // Pointer path with string ending is a reference to another schema.
                if (! is_numeric($lastRef)) {
                     $q = $_native_prop($property, strtolower($lastRef), null, $lastRef);
                     yield $lastRef => $q;
                }

                // Pointer path with numerical ending is an internal property.
                if (
                    $property->type === 'object'
                    && is_numeric($lastRef)
                    && isset($property->properties)
                    && !empty($property->properties)
                ) {
                    foreach ($property->properties as $key => $value) {
                        yield $key => $new_obj($value, $key);
                    }
                }
            }
        };

        if ($schema->allOf) {
            foreach ($compositeGenerator($schema->allOf) as $name => $prop) {
                $this->logger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
                $class->addMember($prop);
            }
        }

        return $class;
    }
}
