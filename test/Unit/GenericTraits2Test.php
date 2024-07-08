<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Apply as ApplyInterface;
use FunctionalPHP\FantasyLand\Chain;
use FunctionalPHP\FantasyLand\Functor as FantasyFunctor;
use PhpParser\Builder\Trait_;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Common\PointedTrait;
use Widmogrod\Common\ValueOfTrait;

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
        $b = new TraitConsumer(3);
        $c = $b->extract();
        $this->assertTrue($c === 3);

        // $e hints string, as it should.
        $e = new TraitConsumer('Hello');

        // However, using static of() hints mixed.
        $d = TraitConsumerOf::of('Hello');
    }
}

/**
 * @template IdentityValue The identity contained inside the functor.
 */
trait GenericPointedTraitOf
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

    /**
     * @param IdentityValue $value
     * @return static<IdentityValue>
     */
    public static function of($value)
    {
        return new static($value);
    }
}

/**
 * How to consume a trait that contains PHPStan generics.
 *
 * @template IdentityValue
 * @implements ConsistentConstructorOf<IdentityValue>
 */
class TraitConsumerOf implements ConsistentConstructorOf
{
    /** @use GenericPointedTraitOf<IdentityValue> */
    use GenericPointedTraitOf;

    public function foo(): mixed
    {
        return $this->extract();
    }
}

/**
 * Enforces constructor signature through both PHP and PHPStan.
 *
 * @see https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static
 *
 * @template IdentityValue
 */
interface ConsistentConstructorOf
{
    /**
     * @param IdentityValue $value
     */
    public function __construct($value);
}
