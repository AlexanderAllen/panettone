<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone;

use cebe\openapi\spec\Schema;

/**
 * Unsupported Open API schema use case.
 *
 * @package Panettone
 */
final class UnsupportedSchema extends \UnexpectedValueException
{
    /**
     * @param Schema $schema
     *   The unsupported schema instance.
     * @param string|null $name
     *   Schema items can sometimes be anonymous, this parameter allows you identify them.
     * @param string|null $error
     *   The reason why this schema is not supported.
     */
    public function __construct(Schema $schema, string $name = null, string $error = null)
    {
        $_error = ($name !== null) ? sprintf('[%s] ', $name) : '';
        $_error .= 'Unsupported use case';
        $_error .= ($error !== null) ? sprintf(': %s', $error) : '.';
        $_error .= PHP_EOL;
        $_error .= sprintf('Schema path %s', $schema->getDocumentPosition()->getPointer());

        parent::__construct($error, 1);
    }
}
