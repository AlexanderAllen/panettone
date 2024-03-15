<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone;

use AlexanderAllen\Panettone\Bread\PanSobao;
use ApiPlatform\SchemaGenerator\OpenApi\PropertyGenerator\PropertyGenerator;
use ApiPlatform\SchemaGenerator\PropertyGenerator\PropertyGeneratorInterface;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Class_;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Psr\Log\{NullLogger, LoggerAwareTrait};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;

/**
 * Work in progress.
 * @package AlexanderAllen\Panettone
 */
class ClassGenerator
{
    // use SchemaTraversalTrait;
    use LoggerAwareTrait;

    private PropertyGeneratorInterface $propertyGenerator;

    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->propertyGenerator = new PropertyGenerator();
    }

    /**
     * Builds classes using the schema compoments of the given `OpenApi`.
     *
     * @param OpenApi $openapi
     * @return void
     */
    public function sourceSchemaComponents(OpenApi $openapi): void
    {

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
    }

    /**
     * Builds a in-memory class from the given OpenApi `Schema`.
     *
     * TODO 3/15
     * To do a better, more atomical unit test here, it would be good to reduce
     * the test data size (schema) to a reasonable minimal.
     *
     * Skip the outer function and just feed the schema directly.
     *
     * 3/15
     * References are unresolved when using this function directly, meaning
     * the code cannot read the schema's properties.
     *
     */
    protected function buildClassFromSchema(string $name, Schema $schema): Class_
    {
        $class = new Class_($name);

        $pan = new PanSobao();
        $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
        $pan->setLogger(new ConsoleLogger($output));

        $schemaProperties = [];
        foreach ($pan->generator($schema) as $schemaItem) {
            // References are lacking the properties prop.
            // Can we dereference references from within getSchemaItem?
            if (isset($schemaItem->properties)) {
                $schemaProperties = array_merge($schemaProperties, $schemaItem->properties);
            }
        }

        foreach ($schemaProperties as $propertyName => $schemaProperty) {
            // Will this fail on references too?
            \assert($schemaProperty instanceof \cebe\openapi\SpecObjectInterface);

            $this->logger->info(sprintf('Source property named "%s"', $propertyName));

            $property =
            ($this->propertyGenerator)(
                $propertyName,
                [],
                $class,
                ['schema' => $schema, 'property' => $schemaProperty]
            );

            if ($property) {
                $class->addProperty($property);
            }
        }

        return $class;
    }
}
