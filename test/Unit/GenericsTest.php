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
#[TestDox('PHPStan generic patterns')]
#[CoversNothing]
#[Group('target')]
class GenericsTest extends TestCase
{
    #[Test]
    #[TestDox('Native constructs')]
    public function testFoo(): void
    {
        $add2 = fn ($a) => $a + 2;
        $a = TestFunctorB::of(5);
        $b = $a->map($add2);
        $this->assertTrue($b->extract() == 7);

        $c = TestFunctorB::of(5)->mapStatic($add2);
        $this->assertTrue($c->extract() == 7);
    }
}

/**
 * @template a The value inherited from the Apply interface.
 * @template b The value from the Chain interface.
 * @implements ApplyInterface<a>
 * @implements Chain<b>
 */
class ApplicativeTest implements ApplyInterface, Chain
{
    use PointedTrait;

    public function ap(ApplyInterface $applicable): ApplyInterface
    {
        if (! $applicable instanceof self) {
            throw new \LogicException(sprintf('Applicative must be an instance of %s', self::class));
        }
        return $applicable->bind(function (callable $f) {
            return self::of($f($this->value));
        });
    }

    public function bind(callable $transformation)
    {
        return $transformation($this->value);
    }

    /**
     * @template TReturnValue3 of ApplicativeTest
     * @param callable(a): TReturnValue3 $f
     * @return TReturnValue3 Returns a new instance of itself.
     */
    public function map3(callable $f): ApplicativeTest
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
  * @template a The generic from the FantasyFunctor interface
  * @implements FantasyFunctor<a>
  */
class TestFunctor implements FantasyFunctor
{
    use PointedTrait;
    use ValueOfTrait;

    // public mixed $value;

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

 /**
  * Implementing PHPStan on extended functors.
  *
  * @template IdentityValue The identity contained inside the functor.
  * @template a
  * @extends TestFunctor<a, IdentityValue>
  *
  * @see https://stackoverflow.com/a/75537312
  */
class TestFunctorB extends TestFunctor
{
    /**
     * This compiles locally.
     *
     * Templating the interface compiles locally, but the instance does not
     * have access to the subclass methods.
     *
     * Templating the subclass gives the instance access the local methods,
     * but does not compile locally.
     *
     * @param callable(IdentityValue): static $f
     * @return static A new instance of itself or child.
     */
    public function map(callable $f): FantasyFunctor
    {
        return static::of($f($this->value));
    }

    /**
     * @todo Assert it accepts and return the TestFunctor type?
     *
     * @template TReturnValue2 of self
     * @param callable(IdentityValue): TReturnValue2 $f
     * @return TReturnValue2
     */
    public function mapSubclass(callable $f): FantasyFunctor
    {
        return static::of($f($this->value));
    }

    /**
     * Compiles, and returns correct type with IDE hinting (using static).
     *
     * @param callable(IdentityValue): static $f
     * @return static A new instance of itself or child.
     */
    public function mapStatic(callable $f): static
    {
        return static::of($f($this->value));
    }
}
