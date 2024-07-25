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
 */
enum Law
{
    /**
     * return(x)->bind(f) == f(x)
     *
     * Asserts that operations can be applied with no side effects.
     *
     * Instead of having differing left and right identities, Monoids use a two-
     * sided simple identity.
     */
    case left_identity;

    /**
     * m->bind(return) == m
     */
    case right_identity;

    /**
     * m->bind(f)->bind(g) == m->bind(fn(x) => f(x)->bind(g))
     *
     * Asserts that the operation execution can be ordered arbitrarily, as long
     * as other operations are not interleaved (what is interleaved).
     *
     * Associative operations can be broken into separate parts, executed
     * independently, and eventually applied together.
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
     * @template b
     * @param b $value
     * @return Monad<b>
     */
    public static function return(mixed $value): Monad
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
}


#[TestDox('Laws for Monads')]
#[CoversNothing]
#[Group('target')]
class LawsTest extends TestCase
{
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
