<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Bread;

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
    public static function printFile(Printer $printer, ClassType $class, string $namespace, string $path): void
    {
        $namespace = new PhpNamespace($namespace);
        $namespace->add($class);

        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        // Turn off automatic namespace resolution if you do not want fully qualified namespaces.
        // @see https://doc.nette.org/en/php-generator#toc-class-names-resolving
        $printer->setTypeResolving(false);

        $path = sprintf('%s/%s.php', $path, $class->getName());

        $content = $printer->printFile($file);
        // $this->logger->debug($content);

        file_put_contents($path, $content);
    }
}
