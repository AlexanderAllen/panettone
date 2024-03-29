<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\PanSobao;
use cebe\openapi\{Reader, ReferenceContext};
use cebe\openapi\spec\{OpenApi, Schema, Reference};
use cebe\openapi\exceptions\{TypeErrorException, UnresolvableReferenceException, IOException};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox, Large};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use ApiPlatform\SchemaGenerator\PropertyGenerator\PropertyGeneratorInterface;
use ApiPlatform\SchemaGenerator\OpenApi\PropertyGenerator\PropertyGenerator;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Class_;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Property;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Type\PrimitiveType;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\UnknownClassOrInterfaceException;
use Psr\Log\{LoggerAwareTrait, NullLogger};

// use EasyRdf\Resource as RdfResource;


/**
 * Class for understanding Open API Generators.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[CoversClass(PanSobao::class)]
#[TestDox('OpenApi package tests')]
#[Group('ignore')]
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
     * This test is an example of why you should not use string sources with
     * references.
     */
    #[Test]
    #[TestDox('Prop references from string sources remain resolved')]
    public function propRefsFromStringTest(): void
    {
        $spec = Reader::readFromYaml(
            $this->fixtureSchemaError,
            OpenApi::class,
        );

        $schema_user = $spec->components->schemas['User'];
        self::assertInstanceOf(
            Reference::class,
            $schema_user->properties['contact_info'],
            'Property of OpenApi type "Reference" should remain unresolved'
        );
    }

    #[Test]
    #[TestDox('References in schema remain unresolved')]
    public function simpleRefsFileFailTest(): void
    {
        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/pansobao.yml'),
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
            realpath('tests/fixtures/pansobao.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL,
        );

        $result_user = $spec->components->schemas['User'];
        self::assertContainsOnlyInstancesOf(
            Schema::class,
            $result_user->properties,
            'All references in a schema should be resolved'
        );
    }

    /**
     * @TODO FINAL TEST(s): How to have working, physical references between types.
     */
    #[Test]
    #[TestDox('Nette dumper 1')]
    public function simpleNetteDumperTest(): void
    {
        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/pansobao.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL,
        );

        $result_user = $spec->components->schemas['User'];
        self::assertContainsOnlyInstancesOf(
            Schema::class,
            $result_user->properties,
            'All references in a schema should be resolved'
        );


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
     * The test can only run if `PropertyGenerator.php` is patched (see composer.json).
     * post-patch references are properties without a 'PrimitiveType' property,
     * which remain unresolved and unwritable to file.
     *
     * 3/16.c : Currently even after the spec is fully resolved in the cebe graph, the class
     * builder still returns props with missing primitive type. It's as the
     * memory reference that classbuilder is accessing is outdated, maybe? As long as this is
     * the case this test will FAIL.
     *
     * @throws TypeErrorException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws UnknownClassOrInterfaceException
     */
    // #[Test]
    #[TestDox('Class builder succeeds using resolved properties')]
    public function classBuilderTest(): void
    {
        $spec = Reader::readFromYamlFile(
            realpath('tests/fixtures/pansobao.yml'),
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL,
        );

        self::setLogger(new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG)));

        // $context = new ReferenceContext($spec, realpath('tests/fixtures/pansobao.yml'));
        // $spec->setReferenceContext($context);
        // $spec->setDocumentContext($spec, new JsonPointer(''));
        // $spec->resolveReferences();

        // Before proceeding to the class generator, this assertion tests that
        // all schema properties are instances of Schema and not Reference.
        $result_user = $spec->components->schemas['User'];
        self::assertContainsOnlyInstancesOf(
            Schema::class,
            $result_user->properties,
            'All references in a schema should be resolved'
        );
        $this->logger->info('All User schema prop references are resolved');
        $this->logger->debug(get_class($result_user->properties['contact_info']));

        // Invoke class generator with fully resolved schema (no references).
        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $this->logger->info(sprintf('Source schema "%s"', $name));
            $classes[] = $this->buildClassFromSchema($name, $schema);
        }

        // Test for resolved properties in schema generator results.
        // 3/16 4:27 : With another patch on propgen an 'object' PrimitiveType
        // is returned and this test passes, but is that enough to generate the physical
        // class with correct props? I need a test for physical classes, then.
        [$user, $contact] = $classes;
        foreach ($user->properties() as $prop => $value) {
            self::assertInstanceOf(
                PrimitiveType::class,
                $value->type,
                sprintf("Schema property %s contains PrimitiveType instance", $prop)
            );
        }
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
        // OK: 3.16/3:54pm contact info seems resolved as an object (not reference)
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

            if ($property->type !== null) {
                \assert($property instanceof Property);
                $class->addProperty($property);
            }
        }

        return $class;
    }
}
