<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Apply;
use FunctionalPHP\FantasyLand\Functor as FantasyFunctor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};

/**
 * Assert functor laws using native and custom constructs.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[TestDox('Assert functor laws for:')]
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
 * // $s = new static($f($this->value));
 * // $f($this->value)); - start with this
 *
 * template V
 */
abstract class A
{
   /**
     * template U
     * @template C as callable(V) doc
     *
     * @return C(V)
     */
    abstract public function map(callable $f): callable;

    // public function map(callable $function): Functor;
}

/**
 *      * @template U
     * @template C as callable(T): U
 */

/**
 * REFERENCES
 *
 * Inspiration https://github.com/functional-php/fantasy-land/issues/16
 *
 * @see
 */


/**
 * @template TReturnValue of mixed
 * @param callable(): TReturnValue $callable
 * @return TReturnValue
 *
 * @see https://github.com/phpstan/phpstan/issues/10618
 * @see https://phpstan.org/r/6f180252-1951-442b-a566-6346b9a7750a
 */
function foo(callable $callable): mixed
{
    return $callable();
}


 /**
 * @template T
 */
class Identity
{
   /**
     * @template U
     * @template C as callable(T): U
     *
     * @param Apply<C> $applicative
     * @return Apply<U>
     */
    public function ap2(Apply $applicative): Apply
    {
        if (! $applicative instanceof self) {
            throw new \LogicException(sprintf('Applicative must be an instance of %s', self::class));
        }

        return $applicative->bind(function (callable $f) {
            return self::of($f($this->value));
        });
    }

    /**
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
     */
    public function foo(callable $callable): mixed
    {
        return $callable();
    }
}
