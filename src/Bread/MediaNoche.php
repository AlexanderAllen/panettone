<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use cebe\openapi\SpecObjectInterface;
use cebe\openapi\spec\{OpenApi, Schema, Reference};
use Psr\Log\{NullLogger, LoggerAwareTrait};
use Psr\Log\LoggerInterface;
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
     * @return \Generator<int>
     */
    public function generate(): \Generator
    {
        $this->logger->info('1');
        yield 1;
        yield 2;
        $this->logger->info('3');
        yield 3;
    }

    /**
     * Recursive iterator for cebe\openapi schema objects.
     *
     * 3/14
     * The beefs I have with the implemenation are:
     *  - nested fors
     *  - dynamic variable access on for loop
     *  - recursive function to top it all
     *
     * I can live with recursion if the other two are cleaned up.
     *
     * 3/18 Removing assertions from generator. Worst possible place to do assertions.
     * Either you know what you're passing down (validate elsewhere), or don't call it.
     *
     * @return \Generator<int, Schema|Reference, null, void>
     */
    public function generator(Schema|Reference $schema): \Generator
    {
        if (!isset($schema->oneOf) && !isset($schema->allOf)) {
            yield $schema;
            return;
        }

        // Resolve references ?
        // References are resolved beforehand by cebe.

        if ($schema->oneOf) {
            // Only use the first oneOf item. Needs to be improved.
            $oneOfSchema = $schema->oneOf[0];
            foreach ($this->generator($oneOfSchema) as $schemaItem) {
                yield $schemaItem;
            }
        }

        foreach ($schema->allOf ?? [] as $allOfSchema) {
            // \assert($allOfSchema instanceof \cebe\openapi\SpecObjectInterface);
            foreach ($this->generator($allOfSchema) as $schemaItem) {
                yield $schemaItem;
            }
        }

        // Once all items have been used, yield the main schema in case there are some properties in it.
        yield $schema;
    }

    /**
     * Recursive property generator for Nette.
     *
     * Creates nette class `Property` objects from cebe `Schema` objects.
     *
     * @return \Generator<string, Property, null, void>
     */
    public function propertyGenerator(Schema $schema): \Generator
    {
        foreach ($schema->properties as $name => $property) {
            $this->logger->debug(sprintf('Parsing property: %s', $name));

            /**
             * What to do about internal and/or recursive objects (no References).
             *
             * 3/18 Ideally I'd want a physical reference to another Type.
             * 3/19 This is the flattened/merged recursive design.
             */
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
