<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see ApiPlatform\SchemaGenerator\OpenApi\SchemaTraversalTrait;
 */

declare(strict_types=1);

namespace AlexanderAllen\Panettone;

use cebe\openapi\SpecObjectInterface;

/**
 * Trait for traversing through Schema.
 *
 * Both Schema and Reference implement SpecObjectInterface.
 */
trait SchemaTraversalTrait
{
    /**
     * Returns a `Generator` object.
     *
     * The interface does not have ->properties, the subclasses Schema and Ref do.
     * Should the return val be resolved to one of those ? (probably)
     *
     * @return \Generator<int, SpecObjectInterface>
     */
    private function getSchemaItem(SpecObjectInterface $schema): \Generator
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
            \assert($oneOfSchema instanceof \cebe\openapi\SpecObjectInterface);
            foreach ($this->getSchemaItem($oneOfSchema) as $schemaItem) {
                yield $schemaItem;
            }
        }

        // $schema->resolveReferences();
        // $allOfSchema->resolveReferences();
        // $allOfSchema->resolveReferences($schema);

        // TODO References are not being resolved and getting muted at the OAS source.
        // Mixed objects that have their own plus external references are not merging.

        foreach ($schema->allOf ?? [] as $allOfSchema) {
            \assert($allOfSchema instanceof \cebe\openapi\SpecObjectInterface);
            foreach ($this->getSchemaItem($allOfSchema) as $schemaItem) {
                yield $schemaItem;
            }
        }

        // Once all items have been used, yield the main schema in case there are some properties in it.
        yield $schema;
    }
}
