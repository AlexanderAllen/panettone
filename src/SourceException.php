<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone;

/**
 * Something wrong with the ingredients in this cake.
 * @package Panettone
 */


final class SourceException extends \UnexpectedValueException
{
    public function __construct(string $message = "", ?\Throwable $source = null)
    {
        // some code

        // make sure everything is assigned properly
        parent::__construct($message, 1, $source);
    }
}
