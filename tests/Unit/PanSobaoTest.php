<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

// use AlexanderAllen\Panettone\ClassGenerator;
use AlexanderAllen\Panettone\Bread\PanSobao;
use cebe\openapi\{Reader, ReferenceContext};
use cebe\openapi\spec\{OpenApi, Schema, Reference, Components};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox, Large};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Class_;
use ApiPlatform\SchemaGenerator\OpenApi\PropertyGenerator\PropertyGenerator;
use ApiPlatform\SchemaGenerator\PropertyGenerator\PropertyGeneratorInterface;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Property;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\exceptions\IOException;
use Psr\Log\LoggerAwareTrait;

/**
 * Class for understanding Open API Generators.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[CoversClass(PanSobao::class)]
#[TestDox('Generator loops')]
#[Group('proof')]
#[Large]
class PanSobaoTest extends TestCase
{
    use LoggerAwareTrait;

    private PropertyGeneratorInterface $propertyGenerator;
    private string $fixtureSchemaError;

    /**
     * Simple references will always give
     * [error] Error sourcing schema: "ApiPlatform\SchemaGenerator\OpenApi\Model\Type\PrimitiveType::__construct(): Argument #1 ($name) must be of type string, null given, called in ...src/OpenApi/PropertyGenerator/PropertyGenerator.php on line 116"
     * @return void
     */
    public function setUp(): void
    {
        $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
        self::setLogger(new ConsoleLogger($output));

        /**
         * Simple reference test / example from SmartBear.
         * See "Nested Objects" in https://swagger.io/docs/specification/data-models/data-types/
         *
         * 3/15
         * cebe doesn't have any problem reading this, it's the api-platform prop generator.
         */
        $this->fixtureSchemaError = <<<'schemaFixture'
        openapi: 3.0.1
        info:
          title: Panettone
          version: 1.0.0
          description: Test schema for Panettone
        servers:
          - url: https://localhost
        paths:
          /me:
            get:
              summary: Returns the authenticated users information.
        components:
          schemas:
            User:
              type: object
              properties:
                id:
                  type: integer
                name:
                  type: string
                contact_info:
                  $ref: '#/components/schemas/ContactInfo'
            ContactInfo:
              type: object
              properties:
                email:
                  type: string
                  format: email
                phone:
                  type: string
        schemaFixture;
    }

    #[Test]
    #[TestDox('Dummy generator loop with foreach')]
    public function first(): void
    {
        $class = new PanSobao();
        $yields = [];
        foreach ($class->generate() as $key => $value) {
            $yields[$key] = $value;
        }
        self::assertCount(3, $yields);
    }

    /**
     * All `Reference` objects will be replaced with their referenced spec objects.
     *
     * @return void
     * @throws TypeErrorException
     */
    #[Test]
    #[TestDox('Component references are replaced')]
    public function third(): void
    {
        $this->propertyGenerator = new PropertyGenerator();

        $openapi = Reader::readFromYaml(
            $this->fixtureSchemaError,
            OpenApi::class,
            true
        );

        $classes = [];
        try {
            foreach ($openapi->components->schemas as $name => $schema) {
                $this->logger->info(sprintf('Source schema "%s"', $name));
                \assert($schema instanceof Schema);
                $classes[] = $this->buildClassFromSchema($name, $schema);
            }
            $test = null;
        } catch (\cebe\openapi\exceptions\TypeErrorException $error) {
            $this->logger->error('The YAML provided is whack');
            throw $error;
        } catch (\Throwable $source) {
            $this->logger->error(sprintf('Error sourcing schema: "%s"', $source->getMessage()));
        }

        $test = null;
    }

    /**
     * Alternative loop using while instead of foreach.
     *
     * 3/14
     * This test is waaay too large.
     * I need to test the inner components of the schema generators, and doing
     * so from the upper ClassGenerator is not gonna cut it.
     *
     * 3/15
     * ClassGenerator brought inline for better control and visibility.
     * Next step is to source reproducible, atomic schemas that can be actually
     * be unit tested, as opposed to production behemoths.
     *
     * For example: make sure that a compoment containing a reference has the
     * reference replaced in the result.
     */
    // #[Test]
    #[TestDox('test class generator')]
    public function second(): void
    {
        $this->propertyGenerator = new PropertyGenerator();

        // First phase, read in schema file.
        $openapi = Reader::readFromYamlFile(
            realpath('schema/soundcloud/oas-1.0.1.yml'),
            OpenAPI::class,
            true,
        );

        $classes = [];
        try {
            foreach ($openapi->components->schemas as $name => $schema) {
                $this->logger->info(sprintf('Source schema "%s"', $name));
                \assert($schema instanceof Schema);
                $classes[] = $this->buildClassFromSchema($name, $schema);
            }
        } catch (\Throwable $source) {
            $this->logger->error(sprintf('Error sourcing schema "%s"', $name));
        }
        $test = null;
    }

    /**
     * Iterate external generator.
     *
     * Extract and aggregate property values from given schema for a given class.
     *
     * Moved to a smaller function to reduce the unit complexity.
     * Also creates a cleaner, more encapsulated logical unit.
     *
     * @param \Generator<int, Schema|Reference> $generator
     *
     * @return array<string, Schema|Reference>
     *   Array containing the properties of a given Schema component.
     */
    private function iterateGenerator(\Generator $generator): array
    {
        $schemaProperties = [];
        // Returns true while the generator is open.
        while ($generator->valid()) {
            // Current resumes generator.
            $schemaItem = $generator->current();

            // Only Schema types have properties, Reference types do not.
            if ($schemaItem instanceof Schema) {
                // Maybe dont run test assertions in inner scopes?
                // self::assertObjectHasProperty('properties', $schemaItem);
                $schemaProperties = array_merge($schemaProperties, $schemaItem->properties);
            }
            // Invoke the generator to move forward the internal pointer.
            $generator->next();
        }
        return $schemaProperties;
    }

    /**
     * Builds in-memory class from given cebe schema.
     *
     * @param string $name
     * @param Schema $schema
     * @return Class_
     */
    private function buildClassFromSchema(string $name, Schema $schema): Class_
    {
        $class = new Class_($name);

        $pan = new PanSobao();
        $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG); // setup logger for generator
        $pan->setLogger(new ConsoleLogger($output));

        // Initial generator call should not yield.
        /**
         * A good unit test would be confirming what happens when you feed
         * a schema with References.
         */
        $gen = $pan->generator($schema);
        $schemaProperties = $this->iterateGenerator($gen);

        foreach ($schemaProperties as $propertyName => $schemaProperty) {
            $this->logger->info(sprintf('Source property named "%s"', $propertyName));

            // 3/15 prop generator is external because it is not exactly small.
            $property =
            ($this->propertyGenerator)(
                $propertyName,
                [],
                $class,
                ['schema' => $schema, 'property' => $schemaProperty]
            );

            if ($property !== null) {
                \assert($property instanceof Property);
                $class->addProperty($property);
            }
        }

        return $class;
    }
}
