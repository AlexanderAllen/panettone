<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Apply as ApplyInterface;
use FunctionalPHP\FantasyLand\Functor as FantasyFunctor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Common\PointedTrait;

/**
 * Apply PHPStan generic patterns to functional patterns.
 *
 * @package AlexanderAllen\Panettone\Test
 *
 * @see https://phpstan.org/blog/generics-in-php-using-phpdocs
 * @see https://phpstan.org/blog/generics-by-examples
 * @see https://phpstan.org/blog/whats-up-with-template-covariant
 * @see https://github.com/functional-php/fantasy-land/issues/16
 */
#[TestDox('Assert PHPStan generic patterns')]
#[CoversNothing]
#[Group('target')]
class GenericsTest extends TestCase
{
    #[Test]
    #[TestDox('Native constructs')]
    public function testFoo(): void
    {
    }
}

/**
 * @template V The contained value
 */
interface TheInterface
{
    /**
     * @param V $value
     * @return TheInterface<V>
     */
    public function __construct(mixed $value);
}

/**
 * @template a The value inherited from the Apply interface.
 * @implements ApplyInterface<a>
 */
class Applicative implements ApplyInterface
{
    use PointedTrait;

   /**
     * @template U
     * @template C as callable(T): U
     *
     * @param ApplyInterface<C> $applicative
     * @return ApplyInterface<U>
     */
    public function ap(ApplyInterface $applicative): ApplyInterface
    {
        if (! $applicative instanceof self) {
            throw new \LogicException(sprintf('Applicative must be an instance of %s', self::class));
        }
        return $applicative->bind(function (callable $f) {
            return self::of($f($this->value));
        });
    }

    /**
     * @template TReturnValue3 of Applicative
     * @param callable(a): TReturnValue3 $f
     * @return TReturnValue3 Returns a new instance of itself.
     */
    public function map3(callable $f): Applicative
    {
        $s = new self($f($this->value));
        return $s;
    }

    public function map(callable $function): FantasyFunctor
    {
        return $function();
    }
}


 /**
 * @template IdentityValue The identity contained inside the functor.
 */
class TestFunctor
{
    public mixed $value;

    /**
     * @param IdentityValue $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Works fine wihtout any typing.
     */
    public function map(callable $f): callable
    {
        return $f($this->value);
    }

    /**
     * @todo Assert it accepts and return the TestFunctor type?
     *
     * @template TReturnValue2 of TestFunctor
     * @param callable(): TReturnValue2 $f
     * @return TReturnValue2
     */
    public function map2(callable $f): self
    {
        return new self($f());
    }

    /**
     * Is it saying both accept and return the Identity type?
     *
     * @template TReturnValue3 of TestFunctor
     * @param callable(IdentityValue): TReturnValue3 $f
     * @return TReturnValue3 Returns a new instance of itself.
     */
    public function map3(callable $f): TestFunctor
    {
        $s = new self($f($this->value));
        return $s;
    }

    /**
     * Switch out mixed return type and use Apply instead.
     *
     * @template TReturnValue of Apply
     * @param callable(): TReturnValue $callable
     * @return TReturnValue
     */
    public function bar(callable $callable): Apply
    {
        return $callable();
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
