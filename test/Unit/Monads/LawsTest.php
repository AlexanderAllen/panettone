<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Monads;

use AlexanderAllen\Panettone\Test\Unit\Applicative\Applicative;
use FunctionalPHP\FantasyLand\Apply;
use FunctionalPHP\FantasyLand\Monad;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, TestDox};

/**
 * Monads are a functional container for values.
 *
 * Monads, like functors, are a wrapper or context for values.
 * Like applicatives, monads provide a means to apply a function to the
 * encapsulated value. They also share some laws with monoids.
 *
 * While applicatives wrap a function, monads encapsulate a value.
 * Applicatives use functions with non-lifted values, monads use functions that
 * return a monad of the same type.
 *
 * Applying a function with an applicative wraps the result in an applicative.
 * Functions that return applicatives applied with an applicative therefore
 * return applicatives within applicatives. Monads avoid this nesting by
 * delegating the value encapsulation to bound function.
 *
 * Monads can also be used as a substitute for native flow control statements.
 *
 * Only monad child classes can decide how to implement `apply` and `bind`,
 * since only the implementor knows what to do with the wrapped value.
 */
enum Law
{
    /**
     * return(x)->bind(f) == f(x)
     *
     * Asserts `bind` has no side effects on value `x` or applied function `f`.
     *
     * The result of binding to `f` a value wrapped in a monad is the same as
     * calling the bound function `f` directly on the wrapped value `x`.
     */
    case left_identity;

    /**
     * m->bind(return) == m
     *
     * If you bind the returned value to a monad, you get your monad back.
     *
     * Ensures that wrappers such as `pure`, `of`, `return`, etc. have no
     * effect other than encapsulating the value with the monad.
     */
    case right_identity;

    /**
     * m->bind(f)->bind(g) == m->bind(fn(x) => f(x)->bind(g))
     *
     * Asserts binding the wrapped `x` to `f` then `g` is the same as binding
     * `x` to the composition of `f` within `g`.
     *
     * Advertises the same benefits as other associative and composition laws.
     */
    case associative;

    /**
     * @template a
     * @param Law $case
     * @param MonadBase<a> $m
     * @param callable $f
     * @param callable $g
     * @param mixed $x
     * @return bool
     */
    public static function assert(
        Law $case,
        MonadBase $m,
        callable $f,
        callable $g,
        mixed $x,
    ): bool {
        return match ($case) {
            static::left_identity => $m->return($x)->bind($f) == $f($x),
            static::right_identity => $m->bind([$m, 'return']) == $m,
            static::associative =>
                $m->bind($f)->bind($g) ==
                $m->bind(fn ($x) => $f($x)->bind($g))
        };
    }
}

/**
 * @template a
 * @extends Applicative<a>
 * @implements Monad<a>
 */
abstract class MonadBase extends Applicative implements Monad
{
    /**
     * Alias for `pure`, used by book because Haskell uses `return` for monads.
     *
     * @phpstan-template b
     * @phpstan-param b $value
     * @phpstan-return static
     */
    public static function return(mixed $value): static
    {
        return static::pure($value);
    }

    /**
     * Takes a function and applies it to the stored value.
     *
     * Bind returns values directly instead of wrapped in a context.
     *
     * Method both from book and fantasy land. Sometimes also called `chain` or
     * `flatMap`.
     *
     * @todo The application of bind fails to compile.
     * @todo Generics on upstream fantasy land are borked.
     * @param callable(a): Monad<a> $f
     * @return Monad<a>
     */
    abstract public function bind(callable $f): Monad;
}

/**
 * @template a
 * @extends MonadBase<a>
 */
class IdentityMonad extends MonadBase
{
    public function bind(callable $f): Monad
    {
        return $f($this->get());
    }

    /**
     * Takes a applicative-wrapped value and applies the stored function to it.
     *
     * Apply puts values in a context before returning them, unlike `bind`.
     */
    public function apply(Applicative $a): Applicative
    {
        return static::pure($this->get()($a->get()));
    }

    /**
     * @param Applicative<a> $a
     * @return Applicative<a>
     */
    public function ap(Apply $a): Apply
    {
        return static::pure($this->get()($a->get()));
    }

    /**
     * @param callable $f
     * @return callable
     */
    public function foo(callable $f): callable
    {
        return $f;
    }
}




#[TestDox('Laws for Monads')]
#[CoversNothing]
#[Group('target')]
class LawsTest extends TestCase
{
    /**
     * PHPStan callable has issues with `bind` array callables.
     */
    public function tesCallableArrays(): void
    {
        $t = null;
        /** @var array<string, callable():void> */
        $b = [
            'a' => [IdentityMonad::class, 'a'],
            'b' => [IdentityMonad::class, 'b']
        ];

        /** @var callable(): void $c */
        $c = [IdentityMonad::class, 'a'];

        /** @var callable(): void $c */
        $d = [IdentityMonad::class, 'a'];

        $g = [IdentityMonad::class, 'b'];

        $e = IdentityMonad::return(1);

        $f = $e->foo($d);
        $x = $e->foo($g);
    }

    public function testLeftIdentity(): void
    {
        $r = Law::assert(
            Law::left_identity,
            IdentityMonad::return(20),
            fn ($a) => IdentityMonad::return($a + 10),
            fn ($a) => IdentityMonad::return($a * 2),
            10
        );
        $this->assertTrue($r);
    }

    public function testRightIdentity(): void
    {
        $r = Law::assert(
            Law::right_identity,
            IdentityMonad::return(20),
            fn ($a) => IdentityMonad::return($a + 10),
            fn ($a) => IdentityMonad::return($a * 2),
            10
        );
        $this->assertTrue($r);
    }

    public function testAssociativity(): void
    {
        $r = Law::assert(
            Law::associative,
            IdentityMonad::return(20),
            fn ($a) => IdentityMonad::return($a + 10),
            fn ($a) => IdentityMonad::return($a * 2),
            10
        );
        $this->assertTrue($r);
    }
}
