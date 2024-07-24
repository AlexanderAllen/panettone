<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Monads;

use AlexanderAllen\Panettone\Test\Unit\Applicative\Applicative;
use FunctionalPHP\FantasyLand\Apply;
use FunctionalPHP\FantasyLand\Chain;
use FunctionalPHP\FantasyLand\Monad as FantasyLandMonad;
use FunctionalPHP\FantasyLand\Functor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, TestDox};
use Widmogrod\Common\PointedTrait;

use function Widmogrod\Functional\compose;

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

    public static function assert(
        Law $case,
        Monad $m,
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
 * A monoid is a combination of a type, a binary operation on the type, and a
 * associated identity element (this can be worded better, no need for three segments).
 *
 * `id` and `op` methods are abstract as those are implementation-specific.
 *
 * An example use case for monoids is folding a collection of values with the
 * same type as the monoid class.
 */
interface Monad
{
    public static function id(): mixed;
    public static function op(mixed $a, mixed $b): mixed;
    /**
     * @param array<mixed> $values
     */
    public static function concat(array $values): mixed;

    public static function return(mixed $value): Monad;
    public static function bind(callable $f): Monad;

    /**
     * Values that cannot be modified directly are considered pure.
     * Pure is used to create a new applicative from any callable.
     *
     * Use local generics on static functions.
     *
     * @template b
     * @param b $value
     * @return FantasyLandMonad<b>
     */
    public static function pure($value): FantasyLandMonad;
}

/**
 * @template a
 * @extends Applicative<a>
 * @implements FantasyLandMonad<a>
 */
abstract class MonadBase extends Applicative implements FantasyLandMonad
{
    /**
     * Method from the book.
     *
     * @template b
     * @param b $value
     * @return FantasyLandMonad<b>
     */
    public static function return(mixed $value): FantasyLandMonad
    {
        return static::pure($value);
    }

    /**
     * Method both from book and fantasy land.
     *
     * @todo Generics on upstream fantasy land are borked.
     * @param callable(a): FantasyLandMonad<a> $f
     * @return FantasyLandMonad<a>
     */
    abstract public function bind(callable $f): FantasyLandMonad;
}

/**
 * @template a
 * @extends MonadBase<a>
 */
class IdentityMonad extends MonadBase
{
    public function bind(callable $f): FantasyLandMonad
    {
        return $f($this->get());
    }

    public function apply(Applicative $a): Applicative
    {
        return static::pure($this->get() ($a->get()));
    }
}


#[TestDox('Laws for Monoids')]
#[CoversNothing]
#[Group('ignore')]
class LawsTest extends TestCase
{
    public function testIdentity(): void
    {

    }

}
