<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

// use AlexanderAllen\Panettone\ClassGenerator;
use AlexanderAllen\Panettone\Bread\PanSobao;
use cebe\openapi\{Reader, ReferenceContext};
use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\{OpenApi, Schema, Reference};
use cebe\openapi\exceptions\{TypeErrorException, UnresolvableReferenceException, IOException};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox, Large};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Class_;
use ApiPlatform\SchemaGenerator\OpenApi\PropertyGenerator\PropertyGenerator;
use ApiPlatform\SchemaGenerator\PropertyGenerator\PropertyGeneratorInterface;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Property;
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
    #[TestDox('PHPUnit sanity check with dummy generator')]
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
     * 3/16 should add to this test the assertion that refernces in string sources
     * remain unresolved (and the test should pass).
     *
     * 3/16.b : This test is an example of what you SHOULD NOT do with cebe: using
     * a string source with references.
     *
     * @throws TypeErrorException
     * @throws UnresolvableReferenceException
     * @throws IOException
     */
    #[Test]
    #[TestDox('Prop references from string sources remain resolved')]
    public function propRefsFromStringTest(): void
    {
        $spec = Reader::readFromYaml(
            $this->fixtureSchemaError,
            OpenApi::class,
        );

        // $classes = [];
        // foreach ($openapi->components->schemas as $name => $schema) {
        //     $this->logger->info(sprintf('Source schema "%s"', $name));
        //     \assert($schema instanceof Schema);
        //     $classes[] = $this->buildClassFromSchema($name, $schema);
        // }

        $schema_user = $spec->components->schemas['User'];
        self::assertInstanceOf(
            Reference::class,
            $schema_user->properties['contact_info'],
            'All references in a schema should be resolved'
        );
    }

    #[Test]
    #[TestDox('References in schema remain unresolved')]
    public function simpleRefsFileFailTest(): void
    {
        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/reference.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_INLINE,
        );

        $result_user = $spec->components->schemas['User'];
        self::assertNotContainsOnly(
            Schema::class,
            $result_user->properties,
            false,
            'Schema still contains Reference properties'
        );
    }

    #[Test]
    #[TestDox('References in schema properties are resolved')]
    public function simpleRefsFileTest(): void
    {
        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/reference.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL,
        );

        // $context = new ReferenceContext($spec, realpath('tests/fixtures/reference.yml'));
        // $spec->setReferenceContext($context);
        // $spec->setDocumentContext($spec, new JsonPointer(''));
        // $spec->resolveReferences();

        $result_user = $spec->components->schemas['User'];
        self::assertContainsOnlyInstancesOf(
            Schema::class,
            $result_user->properties,
            'All references in a schema should be resolved'
        );
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
