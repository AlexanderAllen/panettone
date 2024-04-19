<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use cebe\openapi\spec\{Schema, Reference};
use loophp\collection\Collection;
use Nette\PhpGenerator\Property;
use Nette\PhpGenerator\Type;
use RuntimeException;

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

        $newProp = (new Property($propName))
            ->setReadOnly(true)
            ->setComment($property->description)
            ->setValue($property->default);

        if ($property->nullable) {
            $newProp->setNullable(true);
        }

        // The star logic does not trigger for root schemas of star type,
        // only for child schema properties of type star.

        $starProps = self::getStarProps($property);
        // $starProps['oneOf'][0]->getDocumentPosition()->getPath()
        // $starProps['oneOf'][0]->getDocumentPosition()->getPointer()

        // Dereference schemas.
        $lastRefs = self::derefSchemaNames($starProps);

        // If star allOf: intersection, anyOf|oneOf: union.
        if (!empty($starProps)) {
            foreach ($lastRefs as $starType => $starRefs) {
                if ($starType == 'allOf') {
                    $newProp->setType(Type::intersection(...$starRefs));
                } else {
                    $newProp->setType(Type::union(...$starRefs));
                }
            }
            return $newProp;
        }

        // The not keyword is not an array.
        if (isset($property->not)) {
            $newProp->setType('mixed');
            return $newProp;
        }

        $newProp->setType(
            self::nativeTypeMatch($property->type, $propName, $typeName)
        );

        return $newProp;
    }

    private static function nativeTypeMatch(string $type, string $propName, string $typeName = null): string
    {
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

        /* @see https://swagger.io/specification/#data-types */
        return match ($type) {
            'string' => 'string',
            'integer' => 'int',
            'boolean' => 'bool',
            'float', 'double' => 'float',
            'object', 'array' => $normalizer($typeName ?? $propName),
            'date', 'dateTime' => \DateTimeInterface::class,
            default => throw new RuntimeException("Unsupported schema property type {$type}")
        };
    }

    /**
     * Detect starred schemas.
     *
     * @param Schema|Reference $property
     * @return array<string, mixed>
     */
    private static function getStarProps(Schema|Reference $property): array
    {
        // Star keywords are represented in both Open API and cebe as arrays.
        $starProps = [];
        foreach (['allOf', 'anyOf', 'oneOf'] as $star) {
            if (
                isset($property->{$star}) &&
                ! empty($property->{$star})
            ) {
                assert(is_array($property->{$star}), "Schema property {$star} must be of type array");
                $starProps[$star] = $property->{$star};
            }
        }
        return $starProps;
    }

    /**
     * Dereference schemas.
     *
     * Only works when the property is an array, such as `allOf`, etc.
     *
     * @param array<string, array<Schema|Reference>> $starProps
     * @return array<string, array<string>>
     */
    private static function derefSchemaNames(array $starProps): array
    {
        $last = static fn (Schema|Reference $p): string =>
            Collection::fromIterable(
                $p->getDocumentPosition()->getPath()
            )->last('');

        $lastRefs = [];

        foreach ($starProps as $star => $property) {
            foreach ($property as $starRef) {
                $lastRefs[$star][] = $last($starRef);
            }
        }

        return $lastRefs;
    }
}
