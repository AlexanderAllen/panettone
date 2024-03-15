<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use cebe\openapi\SpecObjectInterface;
use cebe\openapi\spec\{OpenApi, Schema, Reference};
use Psr\Log\{NullLogger, LoggerAwareTrait};

/**
 * The purpose of this class is to understand and iterate the design of open api
 * `\Generator` components.
 *
 * @phpstan-type SpecLikeObject Schema|Reference
 *
 * @package AlexanderAllen\Panettone\Bread
 */
class PanSobao
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
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
     * Returns a `Generator` object.
     *
     * The interface does not have ->properties, the subclasses Schema and Ref do.
     * Should the return val be resolved to one of those ? (probably)
     *
     * 3/14
     * The problem is Schema does not support references. So maybe an union type?
     * PHPStan types and union types are not accurate, and type hinting limited.
     *
     * 3/14
     * The beefs I have with the implemenation are:
     *  - nested fors
     *  - dynamic variable access on for loop
     *  - recursive function to top it all
     * I can live with recursion if the other two are cleaned up.
     *
     * @param SpecLikeObject $schema
     *
     * @return \Generator<int, SpecObjectInterface>
     */
    public function generator($schema): \Generator
    {
        if (!isset($schema->oneOf) && !isset($schema->allOf)) {
            yield $schema;
            return;
        }

        // Validate schema ?
        // $schema->validate();

        // Resolve references ?

        if ($schema->oneOf) {
            // Only use the first oneOf item. Needs to be improved.
            $oneOfSchema = $schema->oneOf[0];
            // \assert($oneOfSchema instanceof \cebe\openapi\SpecObjectInterface);
            foreach ($this->generator($oneOfSchema) as $schemaItem) {
                yield $schemaItem;
            }
        }

        // $schema->resolveReferences();
        // $allOfSchema->resolveReferences();
        // $allOfSchema->resolveReferences($schema);

        // TODO References are not being resolved and getting muted at the OAS source.
        // Mixed objects that have their own plus external references are not merging.

        foreach ($schema->allOf ?? [] as $allOfSchema) {
            // \assert($allOfSchema instanceof \cebe\openapi\SpecObjectInterface);
            foreach ($this->generator($allOfSchema) as $schemaItem) {
                yield $schemaItem;
            }
        }

        // Once all items have been used, yield the main schema in case there are some properties in it.
        yield $schema;
    }
}
