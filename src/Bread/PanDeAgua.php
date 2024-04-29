<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

/**
 * File generator for Nette class objects.
 *
 * @package AlexanderAllen\Panettone\Bread
 * @see https://doc.nette.org/en/php-generator#toc-namespace
 * @see https://doc.nette.org/en/php-generator#toc-php-files
 */
final class PanDeAgua
{
    /**
     *
     * @param Printer $printer
     * @param ClassType $class
     * @param array<string, mixed> $settings
     * @throws InvalidArgumentException
     * @throws InvalidStateException
     */
    public static function printFile(Printer $printer, ClassType $class, array $settings): void
    {
        $output_path = $settings['file']['output_path'];
        $namespace = $settings['file']['namespace'];
        $comment = $settings['file']['comment'];

        $namespace = new PhpNamespace($namespace);
        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addComment($comment);
        $file->addNamespace($namespace);

        // Turn off automatic namespace resolution if you do not want fully qualified namespaces.
        // @see https://doc.nette.org/en/php-generator#toc-class-names-resolving
        $printer->setTypeResolving(false);

        $path = sprintf('%s/%s.php', $output_path, $class->getName());

        $content = $printer->printFile($file);
        // $this->logger->debug($content);

        file_put_contents($path, $content);
    }

    /**
     * Retrieves settings array.
     *
     * @param string $path Path to settings INI file.
     * @return array<string, mixed>
     */
    public static function getSettings(string $path): array
    {
        static $settings = null;
        if ($settings === null) {
            $settings = parse_ini_file($path, true, INI_SCANNER_TYPED);
        }
        return $settings;
    }
}
