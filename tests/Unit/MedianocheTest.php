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
use PhpParser\Node\Expr\Instanceof_;

use function iter\split;
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
            realpath('tests/fixtures/medianoche.yml'),
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

    public function newNetteClass(Schema $schema, string $class_name, callable $callback = null): ClassType
    {
        $this->logger->debug(sprintf('Creating new class: %s', $class_name));
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
        $normalizer = static fn (string $name) => ucfirst(u($name)->camel()->toString());

        $new_prop = static fn (Schema $property, string $name): Property =>
            /* @see https://swagger.io/specification/#data-types */
            (new Property($name))
                ->setReadOnly(true)
                ->setComment($property->description)
                ->setNullable(true)
                ->setValue($property->default)
                ->setType(
                    match ($property->type) {
                        'string' => 'string',
                        'integer' => 'int',
                        'boolean' => 'bool',
                        'float', 'double' => 'float',
                        'object' => $normalizer($name),
                        'date', 'dateTime' => \DateTimeInterface::class,
                        default => throw new \UnhandledMatchError(),
                    }
                );
        ;

        // Set aside nested cebe objects for additional processing.
        static $nested_objects = [];
        $new_obj = static function (Schema $property, string $name) use ($new_prop, $nested_objects): Property {

            // TODO: Do I create new nested class RECURSIVELY here?
            $nested_objects[$name] = $property;

            // $callback()

            // This will create a new class property with a custom type.
            return $new_prop($property, $name);
        };

        $filter = static fn ($p) => 'object' !== $p->type;

        /**
         * Convert all schema props to cebe props.
         * @var Collection<string, Property> $nette_props
         */
        $nette_props = Collection::fromIterable($schema->properties)->ifThenElse($filter, $new_prop, $new_obj);
        foreach ($nette_props as $name => $prop) {
            $this->logger->debug(sprintf('Add class property: %s', $name));
            $class->addMember($prop);
        }

        return $class;
    }


    public function propertyGenerator(Schema $schema): \Generator
    {
        foreach ($schema->properties as $name => $property) {
            $this->logger->debug(sprintf('Parsing property: %s', $name));

            if ($property->type == 'object') {
                // Start a new internal, recursive generator.
                $this->logger->debug(sprintf('Recursing object property: %s', $name));
                foreach ($this->propertyGenerator($property) as $key => $nette_prop) {
                    yield $key => $nette_prop;
                }
                // Do not yield Schema items, only Property items.
                return;
            }

            /* @see https://swagger.io/specification/#data-types */
            $type = match ($property->type) {
                'string' => 'string',
                'integer' => 'int',
                'boolean' => 'bool',
                'float', 'double' => 'float',
                'object' => $name,
                'date', 'dateTime' => \DateTimeInterface::class,
                default => throw new \UnhandledMatchError(),
            };

            yield $name =>
            (new Property($name))
                ->setType($type)
                ->setReadOnly(true)
                ->setComment($property->description)
                ->setNullable(true)
                ->setValue($property->default);
        }
    }
}
