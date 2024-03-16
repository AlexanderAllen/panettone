<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

// use AlexanderAllen\Panettone\ClassGenerator;
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
use ApiPlatform\SchemaGenerator\OpenApi\Model\Property;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\exceptions\IOException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

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

    public function setUp(): void
    {
        self::setLogger(new NullLogger());
        // self::setLogger(new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG)));

        /**
         * Simple reference test. See https://swagger.io/docs/specification/data-models/data-types/
         *
         * 3/15: cebe doesn't have any problem reading this, it's the api-platform prop generator.
         * 3/16: works as long as propgen is patched.
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
     * Simple test for string literal OpenApi schema, contains reference.
     *
     * This unit tests how simple references are processed. Currently simple
     * refs are a source of grief because there isn't any code to handle them,
     * (only more complex refs are handled).
     *
     * Simple refs are not part of any/allOf, arrays, etc.
     *
     * In order for this test to succeed a patch needs to be applied to
     * vendor/api-platform/schema-generator/src/OpenApi/PropertyGenerator/PropertyGenerator.php
     *
     * @return void
     * @throws TypeErrorException
     */
    #[Test]
    #[TestDox('Simple reference test from string')]
    public function simpleRefsTest(): void
    {
        $this->propertyGenerator = new PropertyGenerator();

        $openapi = Reader::readFromYaml(
            $this->fixtureSchemaError,
            OpenApi::class,
        );

        $classes = [];
        foreach ($openapi->components->schemas as $name => $schema) {
            $this->logger->info(sprintf('Source schema "%s"', $name));
            \assert($schema instanceof Schema);
            $classes[] = $this->buildClassFromSchema($name, $schema);
        }

        self::assertCount(2, $classes, 'In-memory classes are generated');
        self::assertContainsOnlyInstancesOf(Class_::class, $classes, 'Instance formed from correct class');
        [$user, $contact] = $classes;

        /**
         * 3/16
         * TODO: Is the contact_info prop a ref?
         * prop-gen code cannot handle simple refs without a patch.
         *
         * what's the final representatin for 'contact_info' ref prop here?
         *
         * TODO: does using the resolve param when reading from file (instead of string)
         * change the ref prop resolution here (do in another test).
         *
         * Is there a cebe or apiplat method that forces ref resolution at this point?
         * EXPLORE.
         *
         * So the main difference in representatino between 'id' and 'contact_info' is that
         * id has a PrimityType assigned to it, which then gets converted to physical manifestation.
         *
         * contact_info, being a unresolved ref, does not have PrimitiveType, b.c. propgen
         * dies when attempting to generate one, therefore would not get final
         * representation in a file I assume.
         */
        self::assertTrue($user->hasProperty('id'));
        self::assertTrue($user->hasProperty('contact_info'));
        self::assertTrue($contact->hasProperty('phone'));
    }

    /**
     * Alternative loop using while instead of foreach.
     *
     * 3/14
     * This test is waaay too large.
     * I need to test the inner components of the schema generators, and doing
     * so from the upper ClassGenerator is not gonna cut it.
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
        // $pan->setLogger(new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG)));

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
            // prop generator cannot resolve simple schema references (must be commented out).
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