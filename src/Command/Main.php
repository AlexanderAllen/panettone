<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Command;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use AlexanderAllen\Panettone\Setup;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'panettone:bake',
    description: 'Generate PHP types.',
    hidden: false,
    aliases: ['panettone:generate']
)]
final class Main extends Command
{
    use Setup;

    protected function configure(): void
    {
        $this
            ->setHelp('Generates PHP types from a Open API source.')
            ->addArgument('source', InputArgument::REQUIRED, 'Open API YAML source');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            [$spec, $printer] = $this->realSetup($input->getArgument('source'), false);
            $config = $input->hasArgument('config') ? $input->getArgument('config') : null;
            $settings = PanDeAgua::getSettings($config);

            $classes = [];
            foreach ($spec->components->schemas as $name => $schema) {
                $class = MediaNoche::newNetteClass($schema, $name, $settings);
                $classes[$name] = $class;
            }

            foreach ($classes as $name => $class_type) {
                PanDeAgua::printFile(
                    $printer,
                    $class_type,
                    $settings,
                );
            }
        } catch (\Exception | \TypeError $th) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
