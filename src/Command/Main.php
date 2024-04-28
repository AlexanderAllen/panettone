<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Command;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use Nette\PhpGenerator\PsrPrinter as Printer;
use cebe\openapi\{Reader, ReferenceContext};
use cebe\openapi\spec\OpenApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputArgument};
use Symfony\Component\Console\Output\{OutputInterface, ConsoleOutput};

#[AsCommand(
    name: 'panettone:bake',
    description: 'Generate PHP types.',
    hidden: false,
    aliases: ['panettone:generate']
)]
final class Main extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('Generates PHP types from a Open API source.')
            ->addArgument('source', InputArgument::REQUIRED, 'Open API YAML source')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $spec = Reader::readFromYamlFile(
                realpath($input->getArgument('source')),
                OpenAPI::class,
                ReferenceContext::RESOLVE_MODE_ALL
            );
            $printer = new Printer();

            $classes = [];
            foreach ($spec->components->schemas as $name => $schema) {
                $class = MediaNoche::newNetteClass($schema, $name);
                $classes[$name] = $class;
            }

            foreach ($classes as $name => $class_type) {
                $path = 'tmp';
                PanDeAgua::printFile($printer, $class_type, 'Panettone', $path);
            }
        } catch (\Exception $th) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
