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

use Symfony\Component\Console\Application;
use AlexanderAllen\Panettone\Command\Main;

$application = new Application();
$main = new Main();
$application->add($main);
$application->setDefaultCommand($main->getName(), true);
$application->run();