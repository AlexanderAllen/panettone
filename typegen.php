<?php

/**
 * @file
 * Script for generating DTO types from a OAS file.
 *
 * - Specify OAS with cebe openapi.
 * - Iterate through paths to select objects to output, or just use the schema.
 * - Use nette generator to dump object graph into PHP file-based objects.
 *
 * Extra
 * - Is the semantic web dead?
 * - Is it worth to inherit from a semwnatic superclass?
 *
 * Problems with openapi generator
 * - Paths. Only paths ending with a parameter in OAS are considered.
 * - Duplicate schemas, because object resolution is based on paths -> schema.
 * - Instead, it should be just schemas.
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use cebe\openapi\Reader;
use cebe\openapi\ReferenceContext;
use cebe\openapi\spec\OpenApi;
use Consolidation\Log\Logger;
use Symfony\Component\Console\Output\ConsoleOutput;

use AlexanderAllen\Panettone\ClassGenerator;



$openapi = Reader::readFromYamlFile(
    realpath('schema/soundcloud/oas-1.0.1.yml'),
    OpenAPI::class,
    ReferenceContext::RESOLVE_MODE_INLINE
);

$cake = new ClassGenerator();

$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
$cake->setLogger(new Logger($output));

$cake->kneadSchema($openapi);

// Inspiration from vendor/api-platform/schema-generator/src/OpenApi/ClassGenerator.php

// $showClass = null;
// if ($showSchema instanceof Schema) {
//     $showClass = $this->buildClassFromSchema($showSchema, $name, $config);
//     $classes = array_merge($this->buildEnumClasses($showSchema, $showClass, $config), $classes);
// }






echo "hi" . PHP_EOL;
