<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use cebe\openapi\spec\{Schema, Reference};
use loophp\collection\Collection;
use Psr\Log\{NullLogger, LoggerAwareTrait, LoggerInterface};
use Nette\PhpGenerator\Property;
use Nette\PhpGenerator\Type;

use function Symfony\Component\String\u;

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

        $unhandled_type = static fn ($type, $name, $class_name): \UnhandledMatchError =>
            new \UnhandledMatchError(
                sprintf(
                    'Unhandled type "%s" for property "%s" of schema "%s"',
                    $type,
                    $name,
                    $class_name
                )
            );

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

        $last = static fn (Schema|Reference $p, ?bool $list = false): string =>
            Collection::fromIterable(
                $list === false ?
                $p->getDocumentPosition()->getPath() :
                $p->items->getDocumentPosition()->getPath()
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
                default => throw $unhandled_type($property->type, $propName, $class_name),
            }
        );
        return $newProp;
    }
}
