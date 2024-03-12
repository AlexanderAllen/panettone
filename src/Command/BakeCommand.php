<?php

namespace AlexanderAllen\Panettone\Command;

use AlexanderAllen\Panettone\ClassGenerator;
use Consolidation\Log\Logger;
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
class BakeCommand extends Command
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
        $openapi = Reader::readFromYamlFile(
            realpath($input->getArgument('source')),
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

        // ... put here the code to create the user

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}
