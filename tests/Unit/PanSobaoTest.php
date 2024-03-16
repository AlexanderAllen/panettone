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
use Psr\Log\{LoggerAwareTrait, NullLogger};

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

        // Set up propgen.
        $this->propertyGenerator = new PropertyGenerator();
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
     * The test passes when `PropertyGenerator.php` is patched (see composer.json).
     * post-patch references are properties without a 'PrimitiveType' property,
     * which remain unresolved and unwritable to file.
     *
     * @return void
     * @throws TypeErrorException
     */
    #[Test]
    #[TestDox('Simple ref test with string source')]
    public function simpleRefsTest(): void
    {
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
        self::assertTrue($user->hasProperty('id'));
        self::assertTrue($user->hasProperty('contact_info'));
        self::assertTrue($contact->hasProperty('phone'));
    }

    /**
     * Same as the simpleRefsTest but using a file.
     *
     * Reading from file will not resolve simple schema references, even with the
     * `true` parameter on `Reader`.
     *
     * TODO: Is there a cebe or apiplat method that forces ref resolution at this point?
     * EXPLORE!
     */
    #[Test]
    #[TestDox('Simple ref test with file source')]
    public function simpleRefsFileTest(): void
    {
        try {
            $classes = [];

            $openapi = Reader::readFromYamlFile(
                realpath('tests/fixtures/reference.yml'),
                OpenAPI::class,
                true,
            );

            foreach ($openapi->components->schemas as $name => $schema) {
                $this->logger->info(sprintf('Source schema "%s"', $name));
                \assert($schema instanceof Schema);
                $classes[] = $this->buildClassFromSchema($name, $schema);
            }
        } catch (\Throwable $source) {
            $this->logger->error('Error sourcing schema');
        }
        $test = null;
    }

    /**
     * Iterate external generator.
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
        $gen = $pan->generator($schema);
        $schemaProperties = $this->iterateGenerator($gen);

        foreach ($schemaProperties as $propertyName => $schemaProperty) {
            $this->logger->info(sprintf('Source property named "%s"', $propertyName));

            // propgen must be patched for this to work with simple refs.
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
