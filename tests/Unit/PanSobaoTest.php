<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\ClassGenerator;
use AlexanderAllen\Panettone\Bread\PanSobao;
use cebe\openapi\{Reader, ReferenceContext};
use cebe\openapi\spec\{OpenApi, Schema, Reference};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox, Large};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Class_;
use ApiPlatform\SchemaGenerator\OpenApi\PropertyGenerator\PropertyGenerator;
use ApiPlatform\SchemaGenerator\PropertyGenerator\PropertyGeneratorInterface;

/**
 * Class for understanding Open API Generators.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[CoversClass(PanSobao::class), CoversClass(ClassGenerator::class)]
#[TestDox('Generator loops')]
#[Group('proof')]
#[Large]
class PanSobaoTest extends TestCase
{
    private PropertyGeneratorInterface $propertyGenerator;

    /**
     * Simple loop test w/ logger insider generator.
     */
    #[Test]
    #[TestDox('Generator loop using foreach')]
    public function first(): void
    {
        $class = new PanSobao();
        $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
        $class->setLogger(new ConsoleLogger($output));

        foreach ($class->generate() as $key => $value) {
            // echo $key, ' => ', $value, "\n";
            // self::assertIsInt($value);
            self::assertNotNull($value);
        }
    }

    /**
     * Alternative loop using while instead of foreach.
     *
     * Read top level open api schema file.
     * Feed to class generator.
     * Process "actual" generator?
     *
     * TODO some naming conventions needed here,
     * class generator is confusing with actual `Generator`s.
     *
     * Generation happens in multiple phases, I'd be more conforable using a phase-like
     * naming convention for functions.
     *
     * 3/14
     * This test is waaay too large.
     * I need to test the inner components of the schema generators, and doing
     * so from the upper ClassGenerator is not gonna cut it.
     */
    #[Test]
    #[TestDox('test class generator')]
    public function second(): void
    {
        $this->propertyGenerator = new PropertyGenerator();

        // First phase, read in schema file.
        $openapi = Reader::readFromYamlFile(
            realpath('schema/soundcloud/oas-1.0.1.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_INLINE
        );

        // Second phase, setup class processor.
        // The class processor will invoke recursively the schema generator.
        $genclass = new ClassGenerator();
        $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
        $genclass->setLogger(new ConsoleLogger($output));

        // Initiate sourcing process.
        // 3/15 skip top level sourcing and do direclty in test.
        // Keep drilling down till we get directly to the recursive bits.
        // $genclass->sourceSchemaComponents($openapi); // moved to test

        $classes = [];
        try {
            foreach ($openapi->components->schemas as $name => $schema) {
                // $this->logger->info(sprintf('Source schema "%s"', $name));
                \assert($schema instanceof Schema);
                $classes[] = $this->buildClassFromSchema($name, $schema);
            }
        } catch (\Throwable $source) {
            // 3/15 what's the best practice for logging throwables in tests.
            // $this->logger->error(sprintf('Error sourcing schema "%s"', $name));
        }



        // Initial call does not output anything
        // $gen = $genclass->generator();

        // Returns true while the generator is open.
        while ($gen->valid()) {
            // Current resumes generator.
            $current = $gen->current();

            // Invoke the generator to move forward the internal pointer.
            $gen->next();
            self::assertNotNull($current);
        }
    }

    /**
     * Iterate external generator.
     *
     * Extract and aggregate property values from given schema for a given class.
     *
     * Moved to a smaller function to reduce the unit complexity.
     * Also creates a cleaner, more encapsulated logical unit.
     *
     * @return array
     *   Array containing the properties of a given Schema component.
     */
    private function iterateGenerator(PanSobao $genclass, Schema $schema): array
    {
        $schemaProperties = [];
        foreach ($genclass->generator($schema) as $schemaItem) {
            // References are lacking the properties prop.
            // Can we dereference references from within getSchemaItem?

            // My generator uses SpecObjectInterface b.c. it is Reference friendy.
            // But it runs into this properties issue.
            if (isset($schemaItem->properties)) {
                $schemaProperties = array_merge($schemaProperties, $schemaItem->properties);
            }
        }
        return $schemaProperties;
    }

    private function buildClassFromSchema(string $name, Schema $schema): Class_
    {
        $class = new Class_($name);

        $pan = new PanSobao();
        $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
        $pan->setLogger(new ConsoleLogger($output));

        $schemaProperties = $this->iterateGenerator($pan, $schema);

        foreach ($schemaProperties as $propertyName => $schemaProperty) {
            // Will this fail on references too?
            \assert($schemaProperty instanceof \cebe\openapi\SpecObjectInterface);

            // $this->logger->info(sprintf('Source property named "%s"', $propertyName));

            // 3/15 linking to yet another external dependency, in this case the property generator class.
            // I don't see any reason why this should not be internal to the class.
            // It's all related to the same behavior, so it's not a trying to do too much use case.
            $property =
            ($this->propertyGenerator)(
                $propertyName,
                [],
                $class,
                ['schema' => $schema, 'property' => $schemaProperty]
            );

            if ($property !== null) {
                $class->addProperty($property);
            }
        }

        return $class;
    }
}
