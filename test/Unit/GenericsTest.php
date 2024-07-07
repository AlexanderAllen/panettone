<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Apply as ApplyInterface;
use FunctionalPHP\FantasyLand\Chain;
use FunctionalPHP\FantasyLand\Functor as FantasyFunctor;
use LogicException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Common\PointedTrait;

/**
 * Apply PHPStan generic patterns to functional patterns.
 *
 * PHPStan generics are an integral part of using Valinor, so understanding them
 * is integral to fully harnessing Valinor's potential in Panettone.
 *
 * Applying functional concepts is the next evolution in Panettone, and while
 * using generics is not required for category theory patterns, generics greatly
 * improve the developer experience by providing accurate type information thus
 * making functional PHP code more accessible to end users (developers).
 *
 * This test suite does not aim to assert the laws of category theory, but rather
 * explore how to adopt PHPStan generics when using functional patterns.
 *
 * The original (circa 2015) and current (stable) widmogrod package does not use
 * generics at all, but dev-master does to some extent. The type information
 * in fantasy-land however may not be compatible with newer PHPStan versions.
 *
 * @todo Consider giving widmo and fantasy some love back if anything formal
 * comes out these tests.
 *
 * @see https://phpstan.org/blog/generics-in-php-using-phpdocs
 * @see https://phpstan.org/blog/generics-by-examples
 * @see https://phpstan.org/blog/whats-up-with-template-covariant
 * @see https://github.com/functional-php/fantasy-land/issues/16
 *
 * @package AlexanderAllen\Panettone\Test
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
 * @template b The value from the Chain interface.
 * @implements ApplyInterface<a>
 * @implements Chain<b>
 */
class Applicative implements ApplyInterface, Chain
{
    use PointedTrait;

//    /**
//      * @template U
//      * @template C as callable(T): U
//      *
//      * @param Applicative<C> $applicative
//      * @return Applicative<U>
//      */

    /**
    //  * @template TReturnValue of Chain
    //  * @param TReturnValue $b
    //  * @return TReturnValue Returns a new instance of itself.
    //  * @throws LogicException
    //  */

    /**
     * @param ApplyInterface<a> $applicable
     * @return Chain<b> Returns a new instance of itself.
     * @todo Apply compliles, but is it correct?
     */
    public function ap(ApplyInterface $applicable): ApplyInterface
    {
        if (! $applicable instanceof self) {
            throw new \LogicException(sprintf('Applicative must be an instance of %s', self::class));
        }
        return $applicable->bind(function (callable $f) {
            return self::of($f($this->value));
        });
    }

    /**
     * @inheritdoc
     * @see vendor/widmogrod/php-functional/src/Monad/Identity.php
     */
    public function bind(callable $transformation)
    {
        return $transformation($this->value);
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
  * Explores basic generic concepts then applies them to functor patterns.
  *
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
     * Switch out mixed return type and use ApplyInterface instead.
     *
     * @template TReturnValue of ApplyInterface
     * @param callable(): TReturnValue $callable
     * @return TReturnValue
     */
    public function bar(callable $callable): ApplyInterface
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
