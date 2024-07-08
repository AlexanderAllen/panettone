<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};

/**
 * How to deploy PHPStan generics when using traits.
 */
#[TestDox('Using traits with PHPStan generics')]
#[CoversNothing]
#[Group('target')]
class GenericTraits2Test extends TestCase
{
    #[Test]
    public function testGenericContainers(): void
    {
        $b = new TraitConsumerOf(3);
        $c = $b->extract();
        $this->assertTrue($c === 3);

        $e = new TraitConsumerOf('Hello');
        $x = $e->extract(); // hinted as string, correctly.

        // However, using static of() hints mixed.
        // $d = TraitConsumerOf::of('Hello');
        // $f = $d->extract(); // mixed here too, that's unnaceptable.
    }
}

/**
 * Enforces constructor signature through both PHP and PHPStan.
 *
 * @see https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static
 *
 * @template ConstructorValue
 */
interface ConsistentConstructorOf
{
    /**
     * @param ConstructorValue $value
     */
    public function __construct($value);
}


/**
 * @template IdentityValue The identity contained inside the functor.
 */
trait GenericPointedTrait2
{
    /**
     * @var IdentityValue
     */
    protected mixed $value;

    /**
     * Ensure everything on start.
     *
     * @param IdentityValue $value
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * @return IdentityValue
     */
    public function extract()
    {
        return $this->value;
    }
}

/**
 * @template IdentityValue
 */
class TraitConsumerOf
{
    /** @use GenericPointedTrait2<IdentityValue> */
    use GenericPointedTrait2;

    public function foo(): mixed
    {
        return $this->extract();
    }
}

// $b = new TraitConsumer(3);
// $c = $b->extract();
// assert($c === 3);

// // $e hints string, as it should.
// $e = new TraitConsumer('Hello');
// $x = $e->extract(); // hinted as string, correctly.

// // However, using static of() hints mixed.
// $d = TraitConsumerOf::of('Hello');
// $f = $d->extract(); // mixed here too, that's unnaceptable.
