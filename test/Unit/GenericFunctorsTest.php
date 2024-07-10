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
     * @template TReturnValue
     * @param callable(IdentityValue): TReturnValue $f
     * @return TReturnValue Returns a new instance of itself.
     */
    public function map(callable $f)
    {
        return static::of($f($this->value));
    }

    /**
     * @template a
     * @template b
     * @param callable(a): b $f
     * @return b
     */
    public function map2(callable $f)
    {
        return $f($this->value);
    }

    /**
     * @return IdentityValue
     */
    public function extract()
    {
        return $this->value;
    }
}
