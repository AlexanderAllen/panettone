<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox};
use Psr\Log\{LoggerAwareTrait, NullLogger};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use cebe\openapi\{Reader, ReferenceContext};
use cebe\openapi\spec\{OpenApi, Schema, Reference};
use cebe\openapi\exceptions\{TypeErrorException, UnresolvableReferenceException, IOException};
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;
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
    #[Test]
    #[TestDox('Dump cebe graph into nette files')]
    public function simpleRefsFileTest(): void
    {
        self::setLogger(new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG)));

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


        // List schemas.
        // $schemas = [];
        // foreach ($spec->components->schemas as $name => $schema) {
        //     $this->logger->info(sprintf('Source schema "%s"', $name));
        //     $schemas[$name] = $schema;
        // }

        $class = new ClassType('User');

        // Test first schema only.
        $schema = $spec->components->schemas['User'];

        // Transform cebe props to nette props.
        foreach ($this->generator($schema->properties) as $key => $nette_prop) {
            $class->addMember($nette_prop);
        }

        $class
            ->setFinal()
            ->addComment("Class description.\nSecond line\n");

        $printer = new Printer();
        // echo $printer->printClass($class);
        $this->logger->debug($printer->printClass($class));


        $test = null;
    }

    /**
     * Nette property generator.
     *
     * @param array<string, Schema|Reference> $props
     * @return \Generator<string, Property, null, void>
     */
    public function generator(array $props): \Generator
    {
        foreach ($props as $name => $schema) {
            $type = $this->nativeType($schema);

            /**
             * I might need to iterate recursively objects?
             * Here a reference is flattened into a Schema object, and the object has it's own properties.
             * 3/18 Do I:
             * Flatten the object properties and merge them?
             * Ideally I'd want a physical reference to another Type.
             */
            if ($schema->type == 'object') {
                // return;
            }

            yield $name =>
            (new Property($name))
                ->setType($type)
                ->setReadOnly(true)
                ->setComment($schema->description)
                ->setNullable($schema->nullable)
                ->setValue($schema->default);
        }
    }

    public function nativeType(Schema|Reference $property): string
    {
        /* @see https://swagger.io/specification/#data-types */
        return match ($property->type) {
            'string' => 'string',
            'integer' => 'int',
            'boolean' => 'bool',
            'float', 'double' => 'float',
            'object' => Schema::class,
            'date', 'dateTime' => \DateTimeInterface::class,
            default => throw new \UnhandledMatchError(),
        };
    }
}
