<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use cebe\openapi\spec\{OpenApi, Schema, Reference};
use Psr\Log\{NullLogger, LoggerAwareTrait, LoggerInterface};
use Nette\PhpGenerator\Property;

/**
 *
 * @package AlexanderAllen\Panettone\Bread
 */
final class MediaNoche
{
    use LoggerAwareTrait;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = ($logger !== null) ? $logger : new NullLogger();
    }

    /**
     * Recursive property generator for Nette.
     *
     * Creates nette class `Property` objects from cebe `Schema` objects.
     * Uses a flatten/merge recursive pattern to retrieve nested object properties.
     *
     * @return \Generator<string, Property, null, void>
     */
    public function propertyGenerator(Schema $schema): \Generator
    {
        foreach ($schema->properties as $name => $property) {
            $this->logger->debug(sprintf('Parsing property: %s', $name));

            if ($property->type == 'object') {
                // Start a new internal, recursive generator.
                $this->logger->debug(sprintf('Recursing object property: %s', $name));
                foreach ($this->propertyGenerator($property) as $key => $nette_prop) {
                    yield $key => $nette_prop;
                }
                // Do not yield Schema items, only Property items.
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
        }
    }
}
