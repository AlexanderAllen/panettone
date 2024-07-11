<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Apply as ApplyInterface;
use FunctionalPHP\FantasyLand\Functor as FantasyFunctor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Common\PointedTrait;
use Widmogrod\Common\ValueOfTrait;

/**
 * Apply PHPStan generic patterns to functional patterns.
 */
#[TestDox('PHPStan generic patterns')]
#[CoversNothing]
#[Group('target')]
class GenericFunctorsTest extends TestCase
{
    #[Test]
    #[TestDox('Native constructs')]
    public function testFoo(): void
    {
        $add2 = fn (int $a): int => $a + 2;
        $a = TestFunctor::of(5);
        $b = $a->map($add2);
        $c = $a->map2($add2);
        $d = $a->map3($add2);
    }
}

/**
 * @template a
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
     * @template b
     * @param b $value
     * @return static<b>
     */
    public static function of($value)
    {
        return new static($value);
    }
}

 /**
  * @template IdentityValue The identity contained inside the functor.
  * @phpstan-consistent-constructor
  */
class TestFunctor
{
    /** @use GenericPointedTrait2<IdentityValue> */
    use GenericPointedTrait2;
    use ValueOfTrait;

    /**
     * @param IdentityValue $value
     */
    public function __construct($value)
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
     * @template TReturnValue
     * @param callable(IdentityValue): TReturnValue $f
     * @return TReturnValue Returns a new instance of itself.
     */
    public function map(callable $f)
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
     * @template a The callable accepts and returns the generic type a
     *
     * @param callable(a): a $f
     * @return static<a> The functor contains callable a as it's value.
     */
    public function map3(callable $f)
    {
        return new static($f($this->value));
    }
}
