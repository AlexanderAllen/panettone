<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use Psr\Log\{LoggerAwareTrait, NullLogger};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use cebe\openapi\{Reader, ReferenceContext};
use cebe\openapi\spec\{OpenApi};
use cebe\openapi\exceptions\{TypeErrorException, UnresolvableReferenceException, IOException};
use Nette\PhpGenerator\Printer;

/**
 * Test suite for file printing.
 *
 * @package AlexanderAllen\Panettone\Test
 * @see https://github.com/AlexanderAllen/panettone/issues/18
 * @see vendor/api-platform/schema-generator/src/Schema/Generator.php
 * @see vendor/api-platform/schema-generator/src/FilesGenerator.php
 */
trait Setup
{
    use LoggerAwareTrait;

    protected function setUp(): void
    {
        $this->setLogger(new NullLogger());
    }

    /**
     * The real fixture method - setup the spec and logging for every test.
     *
     * Most tests in this suite read from a OAS source. This method just cuts
     * down some of that boilerplate, along with some of the logging ceremonies.
     *
     * @param string $spec
     *   The path to the Open API specification.
     * @param bool $log
     *   A Nette Printer instance used for logging and debugging.
     *
     * @return array{OpenApi, Printer}
     *   A tuple with the cebe OAS graph a Nette Printer instance.
     * @throws TypeErrorException
     * @throws UnresolvableReferenceException
     * @throws IOException
     */
    public function realSetup(string $spec, bool $log = false): array
    {
        $this->setLogger($log ?
            new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG)) :
            new NullLogger());

        return [
            Reader::readFromYamlFile(
                realpath($spec),
                OpenAPI::class,
                ReferenceContext::RESOLVE_MODE_ALL,
            ),
            new Printer()
        ];
    }
}
