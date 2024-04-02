<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use AlexanderAllen\Panettone\UnsupportedSchema;
use cebe\openapi\spec\{Schema, Reference};
use loophp\collection\Collection;
use Nette\PhpGenerator\Property;
use Nette\PhpGenerator\Type;

use function Symfony\Component\String\u;

/**
 *
 * @package AlexanderAllen\Panettone\Bread
 */
final class MediaNoche
{
    /**
     * Converts a property from a cebe to a nette object.
     *
     * @param Schema $property
     * @param string $propName
     * @param null|Collection<Property, string> $collection Present when calling from a `Collection::method()`.
     * @param null|string $typeName
     * @param null|string $class_name
     * @return Property
     */
    public static function nativeProp(
        Schema $property,
        string $propName,
        ?Collection $collection = null,
        ?string $typeName = null,
        ?string $class_name = null,
    ): Property {

        /**
         * Custom type identifier.
         *
         * The physical type filename and class name must match.
         * Usually the type (schema/class) is capitalized CamelCase,
         * whereas class properties that reference the types are camel_case.
         *
         * @see api-platform/schema-generator/src/AttributeGenerator/GenerateIdentifierNameTrait.php
         */
        $normalizer = static fn ($name) => ucfirst(u($name)->camel()->toString());

        $last = static fn (Schema|Reference $p): string =>
            Collection::fromIterable(
                $p->getDocumentPosition()->getPath()
            )->last('');

        $newProp = (new Property($propName))
            ->setReadOnly(true)
            ->setComment($property->description)
            ->setNullable(true)
            ->setValue($property->default);

        // $property->anyOf[0]->getDocumentPosition()->parent()->getPointer()
        // "/components/schemas"
        $starred = false;
        $starRefs = [];
        foreach (['allOf', 'anyOf', 'oneOf'] as $star) {
            if (
                isset($property->{$star}) &&
                is_array($property->{$star}) &&
                ! empty($property->{$star})
            ) {
                // $property->type "object"
                // @TODO What happens when you have a native + non-native type union? (test case)
                // and what about *Ofs nested deeper inside properties
                // $starRefs[] = $this->propertyGenerator($property, $propName);
                foreach ($property->{$star} as $starRef) {
                    $starRefs[] = $last($starRef);
                }
                $starred = true;
            }
        }

        if ($starred) {
            $newProp->setType(Type::union(...$starRefs));
            return $newProp;
        }

        $newProp->setType(
            /* @see https://swagger.io/specification/#data-types */
            match ($property->type) {
                'string' => 'string',
                'integer' => 'int',
                'boolean' => 'bool',
                'float', 'double' => 'float',
                'object', 'array' => $normalizer($typeName ?? $propName),
                'date', 'dateTime' => \DateTimeInterface::class,
                default => throw new UnsupportedSchema($property, $propName)
            }
        );
        return $newProp;
    }
}
