<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Command;

use AlexanderAllen\Panettone\Bread\MediaNoche;
use AlexanderAllen\Panettone\Bread\PanDeAgua;
use AlexanderAllen\Panettone\Setup;
use Nette\PhpGenerator\PsrPrinter;
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
            ->addArgument('source', InputArgument::REQUIRED, 'Open API YAML source')
            ->addArgument('config', InputArgument::OPTIONAL, 'Path to .ini configuration file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $config = $input->hasArgument('config') ? $input->getArgument('config') : null;
            $settings = PanDeAgua::getSettings($config);

            $classes = (new MediaNoche())->sourceSchema($settings, $input->getArgument('source'));

            foreach ($classes as $class_type) {
                PanDeAgua::printFile(
                    new PsrPrinter(),
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
