<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Functor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Common\PointedTrait;
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
  * Interfaces and Traits from Widmogrod do not implement generics.
  *
  * FantasyLand does implement generics (on dev-master), however some of their
  * types (such as `Functor->map()`) don't even compile.
  *
  * @template a The identity contained inside the functor.
  * @implements ValueOfInterface<a>
  * @phpstan-consistent-constructor
  *
  * @see \Widmogrod\Common\ValueOfTrait Adds generic support to extract()
  */
class TestFunctor implements ValueOfInterface
{
    /** @use PointedTrait<a> */
    use PointedTrait;

    /**
     * Putting extract on the trait removes the generic typing.
     *
     * @return a
     */
    public function extract()
    {
        return $this->value;
    }

    /**
     * Map with callable accepting and returning class-level generic.
     *
     * @todo Not accurate because a -> b, then returned by callable.
     *
     * @template b The result returned by the callable operation.
     *
     * @param callable(a): b $transformation
     *   Callable `$f` is invoked immediatly with `a`, returning `b` as a result.
     *
     * @return static<b>
     *   A new instance of static containing the result `b` of the calllable
     *   operation `$f`.
     */
    public function map(callable $transformation)
    {
        return static::of($transformation($this->value));
    }

    /**
     * Hints correctly without extending class.
     *
     * @template b The result returned by the callable.
     * @param callable(a): b $f
     * @return b
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
     * The callable `$f` is executed immediatly because it is invoked with
     * the parenthesis.
     *
     * @template b The callable accepts and returns the (local) generic type b.
     *
     * @param callable(a): b $f
     * @return static<b> The functor contains generic value a.
     */
    public function map3(callable $f)
    {
        return new static($f($this->value));
    }
}
