<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone;

use Psr\Log\{LoggerAwareTrait, NullLogger};
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use cebe\openapi\{Reader, ReferenceContext};
use cebe\openapi\spec\{OpenApi};
use cebe\openapi\exceptions\{TypeErrorException, UnresolvableReferenceException, IOException};
use Nette\PhpGenerator\PsrPrinter as Printer;

/**
 * Sources schema from cebe openapi and provides Nette Printer.
 *
 * @package AlexanderAllen\Panettone\Test
 * @see https://github.com/AlexanderAllen/panettone/issues/18
 * @see https://github.com/api-platform/schema-generator/blob/997f6f811faa75006aeff72cec26fe291bb8eaab/src/Schema/Generator.php
 * @see https://github.com/api-platform/schema-generator/blob/997f6f811faa75006aeff72cec26fe291bb8eaab/src/FilesGenerator.php
 */
trait Setup
{
    use LoggerAwareTrait;

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

    /**
     * Return the class-string for the protected logger instance.
     * @return class-string
     */
    public function getLoggerClass(): string
    {
        return ($this->logger)::class;
    }
}
