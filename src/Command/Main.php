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
    name: 'panettone',
    description: 'Generate PHP types from Open API sources.',
    hidden: false,
)]
final class Main extends Command
{
    use Setup;

    protected function configure(): void
    {
        $this
            ->setHelp('Generates PHP types from a Open API source.')
            ->addArgument('input', InputArgument::REQUIRED, 'Path to Open Api source file in YAML format')
            ->addArgument('output', InputArgument::OPTIONAL, 'Destination for generated files')
            ->addArgument('config', InputArgument::OPTIONAL, 'Path to .ini configuration file', 'settings.ini');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $settings = PanDeAgua::getSettings($input->getArgument('config'));

            // Command line options override configuration file settings.
            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $settings['debug'] = true;
            }

            if ($input->getArgument('output')) {
                $settings['file']['output_path'] = $input->getArgument('output');
            }

            $classes = (new MediaNoche())->sourceSchema($settings, $input->getArgument('input'));

            foreach ($classes as $class_type) {
                PanDeAgua::printFile(
                    new PsrPrinter(),
                    $class_type,
                    $settings,
                );
            }
        } catch (\Exception | \TypeError $e) {
            $output->writeln($output->getFormatter()->getStyle('error')->apply($e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
