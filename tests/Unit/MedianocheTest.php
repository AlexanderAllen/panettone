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
        // $logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
        // self::setLogger($logger);

        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/reference.yml'),
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
     * Try linking from physical class User to physical class ContactInfo.
     *
     * Avoid resolving properties recursively in order to get a link from one
     * class Type to another Type.
     */
    #[Test]
    #[TestDox('Dump cebe graph into nette class file')]
    public function cebeToNetteFile(): void
    {
        $logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
        self::setLogger($logger);
        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/reference.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL,
        );

        $schema = $spec->components->schemas['User'];
        $extra = [];
        $class = $this->newNetteClass($schema, 'User', $extra);

        $test = null;

        // $class
        //     ->setFinal()
        //     ->addComment("PHP custom types test");

        // $printer = new Printer();
        // $this->logger->debug($printer->printClass($class));
    }

    /**
     *
     */
    public function newNetteClass(Schema $schema, string $name, array &$classes = []): ClassType
    {
        $class = new ClassType(
            $name,
            (new PhpNamespace('DeyFancyFooNameSpace'))
                ->addUse('UseThisUseStmt', 'asAlias')
        );

        // $classes = [];
        // $props = [];

        // In the process of going through props, we might come acrsos new class applications.
        // We need to be able to receive and store those classes somewhere.
        foreach ($this->propertyGenerator($schema, $classes) as $name => $nette_prop) {
            // self::assertInstanceOf(Property::class, $nette_prop, 'Generator yields Property objects');
            // $props[$name] = $nette_prop;
            if ($nette_prop instanceof Property) {
                $class->addMember($nette_prop);
            }
        }


        // $class->addMember($nette_prop);
        // I could dump the class object into file here, but that would be a internal state violation.

        return $class;
    }

    /**
     * @return \Generator<string, Property|ClassType, null, void|ClassType>
     */
    public function propertyGenerator(Schema|Reference $schema, &$extra = []): \Generator
    {
        foreach ($schema->properties as $name => $property) {
            $this->logger->debug(sprintf('Parsing property: %s', $name));

            if ($property->type == 'object') {
                // Start a new internal, recursive generator.
                $this->logger->debug(sprintf('Encountered nested obj/ref: %s', $name));

                // Do not yield Schema items, only Property items.
                // Still need a Property because the result is being added as a member to a class.
                $PROP_PHPCUSTOMTYPE =
                (new Property($name))
                    ->setType($name)
                    ->setReadOnly(true)
                    ->setComment($property->description)
                    ->setNullable(true)
                    ->setValue($property->default);

                $NETTECLASS_TYPE = $this->newNetteClass($property, $name);
                $extra[$name] = $NETTECLASS_TYPE;

                yield $name => $PROP_PHPCUSTOMTYPE;
                // return $NETTECLASS_TYPE;

                // yield $name => $PROP_PHPCUSTOMTYPE;
                return;
            }

            /* @see https://swagger.io/specification/#data-types */
            $type = match ($property->type) {
                'string' => 'string',
                'integer' => 'int',
                'boolean' => 'bool',
                'float', 'double' => 'float',
                // 'object' => Schema::class,
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

            // return;
        }
    }
}
