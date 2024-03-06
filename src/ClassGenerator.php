<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone;

use ApiPlatform\SchemaGenerator\OpenApi\SchemaTraversalTrait;
use ApiPlatform\SchemaGenerator\OpenApi\PropertyGenerator\PropertyGenerator;
use ApiPlatform\SchemaGenerator\PropertyGenerator\PropertyGeneratorInterface;
use ApiPlatform\SchemaGenerator\OpenApi\Model\Class_;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ClassGenerator {

  use LoggerAwareTrait;
  use SchemaTraversalTrait;

  private PropertyGeneratorInterface $propertyGenerator;

  public function __construct()
  {
    $this->logger = new NullLogger();
    $this->propertyGenerator = new PropertyGenerator();
  }

  public function kneadSchema(OpenApi $openapi): void {

    $classes = [];
    foreach($openapi->components->schemas as $name => $schema) {
      \assert($schema instanceof Schema);
      $classes[] = $this->buildClassFromSchema($name, $schema);
    }

  }

  function buildClassFromSchema(string $name, Schema $schema): Class_
  {
      $class = new Class_($name);

      $schemaProperties = [];
      foreach ($this->getSchemaItem($schema) as $schemaItem) {
          $schemaProperties = array_merge($schemaProperties, $schemaItem->properties);
      }

      foreach ($schemaProperties as $propertyName => $schemaProperty) {
          \assert($schemaProperty instanceof Schema);

          $this->logger->info(sprintf('Evaluating property named "%s"', $propertyName));

          $property = ($this->propertyGenerator)($propertyName, [], $class, ['schema' => $schema, 'property' => $schemaProperty]);
          if ($property) {
              $class->addProperty($property);
          }
      }

      return $class;
  }
}
