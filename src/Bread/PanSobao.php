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
 * @package AlexanderAllen\Panettone\Bread
 */
final class PanSobao
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
}
