<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use AlexanderAllen\Panettone\Test\Setup;
use cebe\openapi\spec\{Schema, Reference};
use Nette\PhpGenerator\Type;
use RuntimeException;
use AlexanderAllen\Panettone\UnsupportedSchema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Property;
use loophp\collection\Collection;
use Nette\InvalidArgumentException;
use UnhandledMatchError;
use Generator;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\EnumType;

use function Symfony\Component\String\u;

/**
 *
 * @package AlexanderAllen\Panettone\Bread
 */
final class MediaNoche
{
    use Setup;

    /**
     * Converts a property from a cebe to a nette object.
     *
     * @param array<string, mixed> $settings
     * @param Schema $property
     * @param string $propName
     * @param null|string $typeName
     * @param null|string $class_name
     * @return Property
     */
    public static function nativeProp(
        array $settings,
        Schema $property,
        string $propName,
        ?string $typeName = null,
        ?string $class_name = null,
    ): Property {

        // The ascii and camel case combo takes care of the illegal characters for PHP symbols.
        $_name = (string) u($propName)->ascii()->camel();

        $newProp = (new Property($_name))->setComment($property->description);

        $nullable = $settings['class']['nullable'] ?? false;

        // Default *can* be set to "null" on settings, but must walk on eggshells
        // because of PHP.
        if (
            array_key_exists('class', $settings) &&
            array_key_exists('default', $settings['class']) &&
            $settings['class']['default'] === null
        ) {
            $newProp->setValue(null);
        }

        if ($property->default !== null) {
            $newProp->setValue($property->default);
        }

        if ($property->nullable || $nullable === true) {
            $newProp->setNullable(true);
        }

        if ($property->readOnly) {
            $newProp->setReadOnly(true);
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
                if ($starType == 'enum') {
                    // Enum properties are simple, non-composite types that reference other objects.
                    // The type name is capitalized because it references an object.
                    $newProp->setType(ucfirst($_name));
                } elseif ($starType == 'allOf') {
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
        foreach (['allOf', 'anyOf', 'oneOf', 'enum'] as $star) {
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
                // Enums are only simple string arrays, not OAS schema references.
                // However, it's easier for nativeProp() to use this logic.
                if ($star === 'enum') {
                    $lastRefs[$star][] = $starRef;
                // Everybody else gets de-referenced.
                } else {
                    $lastRefs[$star][] = $last($starRef);
                }
            }
        }

        return $lastRefs;
    }

    /**
     * Creates a new Nette enumeration object.
     *
     * Use PascalCase per the latest PER-CS recommendations.
     * @see https://www.php-fig.org/per/coding-style/#9-enumerations
     *
     * I do not have an answer for null value enums, therefore supressing null cases.
     * @see https://github.com/AlexanderAllen/panettone/issues/20
     *
     * @param string $name
     * @param array<string> $cases
     * @return EnumType
     */
    private static function newNetteEnum(string $name, array $cases): EnumType
    {
        $pascalCase = fn ($_name) => ucfirst(u($_name)->camel()->toString());
        $enum = new EnumType($pascalCase($name));
        foreach ($cases as $case) {
            if ($case != null) {
                $enum->addCase($pascalCase($case));
            }
        }
        return $enum;
    }

    /**
     * Interprets a given Open Api schema into Nette class instances.
     *
     * @param array<string, mixed> $settings
     * @return array<string, ClassType>
     */
    public function sourceSchema(array $settings, string $source): array
    {
        $debug = false;
        if ($settings['debug'] ??= false) {
            $debug = true;
        }
        [$spec, $printer] = $this->realSetup($source, $debug);

        $classes = [];
        foreach ($spec->components->schemas as $name => $schema) {
            $class = self::newNetteClass($schema, $name, $settings);
            $classes[$name] = $class;
            $this->logger->debug($printer->printClass($class));
        }
        return $classes;
    }

    /**
     * Virtual class generator accepts a cebe object and returns a nette object.
     *
     * Does two things: generate the class, populate it with properties.
     *
     * @TODO Issues #22, #23, namespaces and config file.
     *
     * @param array<string, mixed> $settings
     */
    public static function newNetteClass(Schema $schema, string $class_name, array $settings): ClassType
    {
        $class = new ClassType($class_name);

        $props = self::propertyGenerator($schema, $class_name, $settings);
        foreach ($props as $prop) {
            $class->addMember($prop);
        }

        return $class;
    }

    /**
     * Converts all the properties from a cebe Schema into nette Properties.
     *
     * @param Schema $schema
     * @param string $class_name
     * @param array<string, Property> $settings
     * @return array<Property>
     * @throws UnhandledMatchError
     * @throws InvalidArgumentException
     */
    private static function propertyGenerator(Schema $schema, string $class_name, array $settings): array
    {
        $__props = [];

        self::$staticLogger->debug(sprintf('Processing class %s', $class_name));

        $last = static fn (Schema|Reference $p, ?bool $list = false): string =>
            Collection::fromIterable(
                $list === false ?
                $p->getDocumentPosition()->getPath() :
                $p->items->getDocumentPosition()->getPath()
            )->last('');

        // Schema has type array.
        // Shape: "array schema, items point to single ref"
        if ($schema->type === 'array') {
            // Don't flatten or inline the reference, instead reference the schema as a type.
            self::$staticLogger->debug(sprintf('[%s/%s] Add array class property', $class_name, 'items'));
            $prop = MediaNoche::nativeProp($settings, $schema, 'items', $last($schema), $class_name);
            $__props[] = $prop;
        }

        /**
         * Generator maps cebe Schemas to nette Properties.
         *
         * @param list<Schema|Reference> $array
         *
         * @return Generator<string, Property|ClassLike, null, void>
         */
        $compositeGenerator = function ($array) use ($class_name, $last, $settings): Generator {
            foreach ($array as $key => $property) {
                $lastRef = $last($property);

                if (isset($property->enum)) {
                    yield $lastRef => self::newNetteEnum($key, $property->enum);
                }

                // Pointer path with string ending is a reference to another schema.
                if (! is_numeric($lastRef)) {
                    yield $lastRef => MediaNoche::nativeProp($settings, $property, strtolower($lastRef), $lastRef, $class_name);
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
                        yield $key => MediaNoche::nativeProp($settings, $value, $key);
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
                self::$staticLogger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
                $__props[$name] = $prop;
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

            // This never kicks in b.c.
            // 1) propertyGenerator is non-recurse. Meaning it won't drill down to anyOf props.
            // 2) starGuard won't let top-level schemas marked as anyOf be used.
            //
            // /** @var Property $prop */
            // foreach ($compositeGenerator($schema->anyOf) as $name => $prop) {
            //     self::$staticLogger->debug(sprintf('[%s/%s] Add class property', $class_name, $name));
            //     $__props[$name] = $prop;
            // }
        }

        foreach ($compositeGenerator($schema->properties) as $name => $prop) {
            if ($prop instanceof Property) {
                $__props[$name] = $prop;
            } else {
                // Dump non-property values into a sidecar for later processing.
                self::$sideCar[$name] = $prop;
            }
        }

        return $__props;
    }

    /**  @var array<ClassLike> */
    private static array $sideCar;
}
