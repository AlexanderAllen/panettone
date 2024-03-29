<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use AlexanderAllen\Panettone\Bread\Focaccia;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\Console\Output\ConsoleOutput;
use Consolidation\Log\Logger;

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
#[CoversClass(Focaccia::class)]
#[TestDox('Generator loops')]
#[Group('ignore')]
class FocacciaTest extends TestCase
{
    /**
     * Simple loop test w/ logger insider generator.
     */
    #[Test]
    #[TestDox('Generator loop using foreach')]
    public function first(): void
    {
        $class = new Focaccia();
        $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
        $class->setLogger(new Logger($output));

        foreach ($class->generate() as $key => $value) {
            // echo $key, ' => ', $value, "\n";
            // self::assertIsInt($value);
            self::assertNotNull($value);
        }
    }

    /**
     * Alternative loop using while instead of foreach.
     */
    #[Test]
    #[TestDox('Generator loop using while')]
    public function second(): void
    {
        $class = new Focaccia();

        // Initial call does not output anything
        $gen = $class->generate();

        // Returns true while the generator is open.
        while ($gen->valid()) {
            // Current resumes generator.
            $current = $gen->current();

            // Invoke the generator to move forward the internal pointer.
            $gen->next();
            self::assertNotNull($current);
        }
    }
}
