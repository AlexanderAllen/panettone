<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Monads;

use AlexanderAllen\Panettone\Test\Unit\Applicative\Applicative;
use FunctionalPHP\FantasyLand\Monad as FantasyLandMonad;
use FunctionalPHP\FantasyLand\Functor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, TestDox};
use Widmogrod\Common\PointedTrait;

use function Widmogrod\Functional\compose;

enum Law
{
    /**
     * a op id == id op a == a
     *
     * Asserts that operations can be applied with no side effects.
     *
     * Instead of having differing left and right identities, Monoids use a two-
     * sided simple identity.
     */
    case left_identity;
    case right_identity;

    /**
     * (a op b) op c == a op (b op c)
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
        mixed $a,
        mixed $b,
        mixed $c,
    ): bool {
        return match ($case) {
            static::left_identity => $m->op($m->id(), $a) == $a,
            static::right_identity => $m->op($a, $m->id()) == $a,
            static::associative =>
                $m->op($m->op($a, $b), $c) ==
                $m->op($a, $m->op($b, $c))
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
 *
 * Use native construct to handle safe static usage.
 *
 * @todo Move this further up the interface inheritance chain.
 * @see https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static safe statics
 */
interface PointedInterface
{
    /**
     * @param a $value
     */
    public function __construct($value);
}

/**
 * @template a
 * @extends Applicative<a>
 * @implements FantasyLandMonad<a>
 * @implements PointedInterface<a>
 */
abstract class MonadBase extends Applicative implements FantasyLandMonad, PointedInterface
{
    /** @use PointedTrait<a> */
    use PointedTrait;

    /**
     * @template b
     * @param b $value
     * @return static<b>
     */
    public static function pure($value): FantasyLandMonad
    {
        return new static($value);
    }

    /**
     * @template b
     * @param b $value
     * @return static<b>
     */
    public static function return(mixed $value): FantasyLandMonad
    {
        return static::pure($value);
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
