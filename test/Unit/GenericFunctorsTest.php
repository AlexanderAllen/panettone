<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Functor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Common\ValueOfInterface;

/**
 * Apply PHPStan generic patterns to functional patterns.
 */
#[TestDox('Functors using generics')]
#[CoversNothing]
#[Group('target')]
class GenericFunctorsTest extends TestCase
{
    #[Test]
    public function testCheckTheDumpedHints(): void
    {
        $add2 = fn (int $a): int => $a + 2;
        $a = TestFunctor::of(5);
        $b = $a->map($add2);
        $x = $b->extract();
        $this->assertTrue($x === 7, 'Extracted value retains generic hint');

        $c = $a->map2($add2);
        $this->assertTrue($c === 7, 'Method map2 just returns the callback result');

        $d = $a->map3($add2);
        $this->assertTrue($d->extract() === 7, 'Returns a functor of type int');
    }
}

/**
 * Copy of PointedTrait that provides generics.
 *
 * @template a
 *
 * @see \Widmogrod\Common\PointedTrait
 */
trait GenericPointedTrait2
{
    /**
     * @var a
     */
    protected $value;

    /**
     * Ensure everything on start.
     *
     * @param a $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * In order for generic information to be retained, a local template must
     * be used. Using a non-local generic will revert dumped type to mixed.
     *
     * @template b The local generic type.
     * @param b $value
     * @return static<b>
     *   A new static instance of generic type `b`.
     */
    public static function of($value)
    {
        return new static($value);
    }
}

 /**
  * Interfaces and Traits from Widmogrod do not implement generics.
  *
  * FantasyLand does implement generics (on dev-master), however some of their
  * types (such as `Functor->map()`) don't even compile.
  *
  * @template IdentityValue The identity contained inside the functor.
  * @implements Functor<IdentityValue>
  * @phpstan-consistent-constructor
  *
  * @see \Widmogrod\Common\ValueOfTrait Adds generic support to extract()
  */
class TestFunctor implements ValueOfInterface, Functor
{
    /** @use GenericPointedTrait2<IdentityValue> */
    use GenericPointedTrait2;

    /**
     * @param IdentityValue $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Putting extract on the trait removes the generic typing.
     *
     * @return IdentityValue
     */
    public function extract()
    {
        return $this->value;
    }

    /**
     * Map with callable accepting and returning class-level generic.
     *
     * @todo Returning interface compiles, have not tested implementation.
     *
     * @param callable(IdentityValue): IdentityValue $f
     *   Callable is executed immediatly.
     *
     * @return static<IdentityValue>
     */
    public function map(callable $f): Functor
    {
        return static::of($f($this->value));
    }

    /**
     * Hints correctly without extending class.
     *
     * @template a
     * @param callable(IdentityValue): a $f
     * @return a
     */
    public function map2(callable $f)
    {
        return $f($this->value);
    }

    /**
     * Returns new static directly instead of using static::of method.
     *
     * Observed result and hint is exactly the same as using static::of.
     *
     * The callable `$f` is executed immediatly, while the result of type `a`
     * is fed to the new static constructor.
     *
     * @template a The callable accepts and returns the (local) generic type a.
     *
     * @param callable(a): a $f
     * @return static<a> The functor contains generic value a.
     */
    public function map3(callable $f)
    {
        return new static($f($this->value));
    }
}
