<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Apply as ApplyInterface;
use FunctionalPHP\FantasyLand\Chain;
use FunctionalPHP\FantasyLand\Functor as FantasyFunctor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Common\PointedTrait;
use Widmogrod\Common\ValueOfTrait;

/**
 * Apply PHPStan generic patterns to functional patterns.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[TestDox('PHPStan generic value containers')]
#[CoversNothing]
#[Group('target')]
class GenericContainers extends TestCase
{
    #[Test]
    public function testGenericContainers(): void
    {
        // Generic variable is properly hinted at!
        // GenericValueContainer<int>
        $a = new GenericValueContainer(5);
        $this->assertTrue($a->extract() === 5);
    }
}

 /**
  * Can you get PHPStan to hint at the contained value during extraction?
  *
  * @template IdentityValue The identity contained inside the functor.
  */
class GenericValueContainer
{
    /**
     * @var IdentityValue
     */
    public mixed $value;

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
}

trait GenericPointedTrait
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * Ensure everything on start.
     *
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @inheritdoc
     */
    public static function of($value)
    {
        return new static($value);
    }
}

/**
 * Explores basic generic concepts then applies them to functor patterns.
*
* @template IdentityValue The identity contained inside the functor.
* @template a The generic from the FantasyFunctor interface
* @implements FantasyFunctor<a>
*/
class TestFunctor4 implements FantasyFunctor
{
    use GenericPointedTrait;
    use ValueOfTrait;

    /**
     * Works fine wihtout any typing.
     */
    public function simpleMap(callable $f): callable
    {
        return $f($this->value);
    }

    /**
     * @template TReturnValue of FantasyFunctor
     * @param callable(IdentityValue): TReturnValue $f
     * @return TReturnValue Returns a new instance of itself.
     */
    public function map(callable $f): FantasyFunctor
    {
        return static::of($f($this->value));
    }

    /**
     * @template TReturnValue of mixed
     * @param callable(): TReturnValue $callable
     * @return TReturnValue
     *
     * @see https://github.com/phpstan/phpstan/issues/10618
     * @see https://phpstan.org/r/6f180252-1951-442b-a566-6346b9a7750a
     */
    public function foo(callable $callable): mixed
    {
        return $callable();
    }
}
