<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use cebe\openapi\spec\{Schema, Reference};
use Nette\PhpGenerator\Type;
use RuntimeException;
use AlexanderAllen\Panettone\UnsupportedSchema;
use cebe\openapi\{Reader, ReferenceContext, SpecObjectInterface};
use cebe\openapi\exceptions\{TypeErrorException, UnresolvableReferenceException, IOException};
use Generator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use loophp\collection\Collection;
use UnhandledMatchError;
use Nette\InvalidArgumentException;

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

    /**
     * Virtual class generator accepts a cebe object and returns a nette object.
     *
     * Does two things: generate the class, populate it with properties.
     *
     * @TODO Issues #22, #23, namespaces and config file.
     */
    public static function newNetteClass(Schema $schema, string $class_name): ClassType
    {
        $class = new ClassType(
            $class_name,
            (new PhpNamespace('DeyFancyFooNameSpace'))
                ->addUse('UseThisUseStmt', 'asAlias')
        );

        $props = self::propertyGenerator($schema, $class_name);
        foreach ($props as $key => $value) {
            $class->addMember($value);
        }

        return $class;
    }

    /**
     * Converts all the properties from a cebe Schema into nette Properties.
     *
     * @param Schema $schema
     * @param string $class_name
     * @return array<Property>
     * @throws UnhandledMatchError
     * @throws InvalidArgumentException
     */
    private static function propertyGenerator(Schema $schema, string $class_name): array
    {
        $__props = [];

        $last = static fn (Schema|Reference $p, ?bool $list = false): string =>
            Collection::fromIterable(
                $list === false ?
                $p->getDocumentPosition()->getPath() :
                $p->items->getDocumentPosition()->getPath()
            )->last('');

        // Native type parsing.
        $natives = static fn ($p) => ! in_array($p->type, ['object', 'array'], true);

        /**
         * Convert all cebe schema props to nette props.
         * @var Collection<string, Property> $nette_props
         *
         * @TODO Cleanup per tix #15.
         */
        $nette_props = Collection::fromIterable($schema->properties)->ifThenElse(
            $natives,
            [MediaNoche::class, 'nativeProp'],
            [MediaNoche::class, 'nativeProp'],
        );
        foreach ($nette_props as $name => $prop) {
            // $this->logger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
            $__props[$name] = $prop;
        }

        // Schema has type array.
        // Shape: "array schema, items point to single ref"
        if ($schema->type === 'array') {
            // Don't flatten or inline the reference, instead reference the schema as a type.
            // $this->logger->debug(sprintf('[%s/%s] Add array class property', $class_name, 'items'));
            $prop = MediaNoche::nativeProp($schema, 'items', null, $last($schema), $class_name);
            $__props[] = $prop;
        }

        /**
         * Generator maps cebe Schemas to nette Properties.
         *
         * @param list<Schema|Reference> $array
         *
         * @return Generator<mixed, Property, null, void>
         */
        $compositeGenerator = function ($array) use ($class_name, $last): Generator {
            foreach ($array as $key => $property) {
                $lastRef = $last($property);

                // Pointer path with string ending is a reference to another schema.
                if (! is_numeric($lastRef)) {
                    yield $lastRef => MediaNoche::nativeProp($property, strtolower($lastRef), null, $lastRef, $class_name);
                }

                // Pointer path with numerical ending is an internal property.
                if (
                    $property->type === 'object'
                    && is_numeric($lastRef)
                    && isset($property->properties)
                    && !empty($property->properties)
                ) {
                    // The generator steps through all the object properties, causing them to become "inline", or part
                    // of the generated type.
                    foreach ($property->properties as $key => $value) {
                        yield $key => MediaNoche::nativeProp($value, $key);
                    }
                }
            }
        };

        /**
         * Detect an unsuported use case instance.
         *
         * If a starOf is detected in a schema item whose parent is components/schemas
         * it means it's a top-level starOf schema. While this use case is valid OAS YAML,
         * it represents a use case I'm not supporting.
         */
        $starGuard = function (Schema $schema, string $star) use ($class_name) {
            if ('/components/schemas' == $schema->getDocumentPosition()->parent()->getPointer()) {
                throw new UnsupportedSchema(
                    $schema,
                    $class_name,
                    sprintf('Using %s on a top-level schema component', $star)
                );
            }
        };

        if ($schema->allOf) {
            foreach ($compositeGenerator($schema->allOf) as $name => $prop) {
                // $this->logger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
                $__props[] = $prop;
            }
        }

        /**
         * anyOf schemas:
         * - Generate every type, regardless if it's reference or inline native/object type.
         * - For each schema reference, generate only the type reference, not the type itself.
         * - For schema reference, add type ref to a union of types.
         * - Inline objects and natives are generated inline.
         * - Inline objects are also part of a union, which capture every single type
         *   mentioned in the anyOf.
         *
         * So basically a anyOf generator should return a big ol list of types to be added to a Union.
         * Some of them just references, some of them fully populated objects or native types.
         *
         * @TODO The commen above and code needs some sanity check per #15.
         *
         * @see https://dev.to/drupalista/dev-log-330-anyof-2jgm
         */
        if ($schema->anyOf) {
            $starGuard($schema, 'anyOf');

            /** @var Property $prop */
            foreach ($compositeGenerator($schema->anyOf) as $name => $prop) {
                // $this->logger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
                $__props[] = $prop;
            }
            // $prop->setType()
        }

        return $__props;
    }
}
