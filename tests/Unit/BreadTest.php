<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\BreadGenerator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Class for understanding PHP Generators.
 *
 * Generators were created by Nikita Popov and merged into PHP source in 2012.
 * They provide a more friendlier syntax than the O.G. Iterator interface.
 *
 * Generators are at the heart of API Platform's Schema Generator trait
 * `SchemaTraversalTrait`, which is used to parse and generate types from
 * Open API schemas.
 *
 * This test is a baby example / deconstruction of how generators work, taken
 * from Nikita's RFC examples.
 *
 * See:
 * - https://wiki.php.net/rfc/generators
 * - https://github.com/php/php-src/pull/177
 * - https://www.php.net/manual/en/language.generators.overview.php
 * - https://github.com/api-platform/schema-generator
 * - https://github.com/api-platform/schema-generator/blob/997f6f811faa75006aeff72cec26fe291bb8eaab/src/OpenApi/SchemaTraversalTrait.php
 *
 * I find that the RFC document is richer with history and examples than the
 * final overview page, so I've linked it here.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[CoversClass(BreadGenerator::class)]
#[TestDox('Generator test')]
#[Group('ignore')]
class BreadTest extends TestCase
{
    protected static \Generator $generator;

    protected function setUp(): void
    {
        $class = new BreadGenerator();

        // Initial call does not output anything.
        self::$generator = $class->generate();
    }

    /**
     * Call to current() resumes the generator, thus "start" is echo'd.
     */
    #[Test]
    #[TestDox('Test first yield')]
    public function first(): void
    {
        $this->expectOutputString('start');
        $yield = self::$generator->current();
        self::assertEquals($yield, 'middle');
    }

    /**
     * Then the yield expression is hit and the string "middle" is returned
     * as the result of current() and then echo'd.
     *
     * @return void
     */
    #[Test]
    #[TestDox('Test second yield')]
    #[Depends('first')]
    public function second(): void
    {
        $this->expectOutputString('startend');
        self::$generator->next();
    }
}
