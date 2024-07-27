<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Monoids;

use FunctionalPHP\FantasyLand\Semigroup;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, TestDox};
use RuntimeException;
use Widmogrod\Common\PointedTrait;
use Widmogrod\Common\ValueOfInterface;

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

    /**
     * @template a
     * @param Law $case
     * @param Monoid<a> $m
     * @param mixed $b
     * @param mixed $c
     * @return bool
     */
    public static function assert(
        Law $case,
        Monoid $m,
        mixed $b,
        mixed $c,
    ): bool {
        $a = $m->extract();
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
 * A monoid is a semigroup with an identity element.
 *
 * Monoids combine of a type, a binary operation on the type, and a
 * associated identity element. They are an algebraic structure between
 * semigroups and groups.
 *
 * `id` and `op` methods are abstract as those are implementation-specific.
 *
 * An example use case for monoids is folding a collection of values with the
 * same type as the monoid class.
 *
 * @template a
 * @extends Semigroup<a>
 * @extends ValueOfInterface<a>
 *
 * @link https://en.wikipedia.org/wiki/Monoid Monoid theory
 * @link https://en.wikipedia.org/wiki/Semigroup Semigroup theory
 */
interface Monoid extends Semigroup, ValueOfInterface
{
    public function id(): mixed;
    public function op(mixed $a, mixed $b): mixed;

    /**
     * Return result of applying one semigroup with another.
     *
     * @param Semigroup<a> $value
     * @return Semigroup<a>
     */
    public function concat(Semigroup $value): Semigroup;
}

/**
 * @template a
 * @implements Monoid<a>
 */
abstract class MonoidBase implements Monoid
{
    /** @use PointedTrait<a> */
    use PointedTrait;

    /**
     * @param a $value
     */
    public function set(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return a $value
     */
    public function extract()
    {
        return $this->value;
    }

    /**
     * Identity element.
     *
     * Used as initial value in concat / reduce operations.
     * @return a
     */
    abstract public function id(): mixed;

    /**
     * Operation on set members of the same type.
     *
     * @param mixed $a
     * @param mixed $b
     * @return a
     */
    abstract public function op(mixed $a, mixed $b): mixed;

    /**
     * Concat folds value into a result of type `a`.
     *
     * For example if `a` is an array of `int`, the result is a folded `int`.
     *
     * @param static<a> $value
     * @return static<a>
     */
    public function concat(Semigroup $value): Semigroup
    {
        $a = $value->value;
        $b = array_reduce($a, [$this, 'op'], [$this, 'id']());
        $c = static::of($b);
        return $c;
    }
    // public function __invoke(mixed ...$args): mixed
    // {
    //     switch (count($args)) {
    //         case 0:
    //             throw new RuntimeException('Expects at least 1 parameter.');
    //         case 1:
    //             return function ($b) use ($args) {
    //                 return static::op($args[0], $b);
    //             };
    //         default:
    //             return $this->concat($args);
    //     }
    // }
}

// (new IntSum([1, 2, 3]))->concat()

/**
 * @template a
 * @extends MonoidBase<a>
 */
class IntSum extends MonoidBase
{
    public function id(): int
    {
        return 0;
    }
    public function op(mixed $a, mixed $b): mixed
    {
        return $a + $b;
    }
}

/**
 * @template a
 * @extends MonoidBase<a>
 */
class IntProduct extends MonoidBase
{
    public function id(): int
    {
        return 1;
    }
    public function op(mixed $a, mixed $b): mixed
    {
        return $a * $b;
    }
}

/**
 * @template a
 * @extends MonoidBase<a>
 */
class StringConcat extends MonoidBase
{
    public function id(): string
    {
        return '';
    }
    public function op(mixed $a, mixed $b): mixed
    {
        return $a . $b;
    }
}

/**
 * @template a
 * @extends MonoidBase<a>
 */
class ArrayMerge extends MonoidBase
{
    public function id(): mixed
    {
        return [];
    }
    public function op(mixed $a, mixed $b): mixed
    {
        return array_merge($a, $b);
    }
}

/**
 * @template a
 * @extends MonoidBase<a>
 */
class Any extends MonoidBase
{
    public function id(): bool
    {
        return false;
    }
    public function op(mixed $a, mixed $b): bool
    {
        return $a || $b;
    }
}

/**
 * @template a
 * @extends MonoidBase<a>
 */
class All extends MonoidBase
{
    public function id(): bool
    {
        return true;
    }
    public function op(mixed $a, mixed $b): bool
    {
        return $a && $b;
    }
}

/**
 * @template a
 * @extends MonoidBase<a>
 */
abstract class None extends MonoidBase
{
}

#[TestDox('Laws for Monoids')]
#[CoversNothing]
#[Group('target')]
class LawsTest extends TestCase
{
    public function testIdentity(): void
    {
        $result = Law::assert(Law::left_identity, new IntSum(5), 10, 20);
        $this->assertTrue($result);
        $result = Law::assert(Law::right_identity, new IntSum(5), 10, 20);
        $this->assertTrue($result);

        $result = Law::assert(Law::left_identity, new IntProduct(5), 10, 20);
        $this->assertTrue($result);
        $result = Law::assert(Law::right_identity, new IntProduct(5), 10, 20);
        $this->assertTrue($result);

        $result = Law::assert(Law::left_identity, new StringConcat('Hello '), 'World', '!');
        $this->assertTrue($result);
        $result = Law::assert(Law::right_identity, new StringConcat('Hello '), 'World', '!');
        $this->assertTrue($result);

        $result = Law::assert(Law::left_identity, new Any(true), false, true);
        $this->assertTrue($result);

        $result = Law::assert(Law::right_identity, new Any(false), false, true);
        $this->assertTrue($result);

        $result = Law::assert(Law::left_identity, new All(true), false, true);
        $this->assertTrue($result);

        $result = Law::assert(Law::right_identity, new All(false), false, true);
        $this->assertTrue($result);
    }

    public function testAssociativity(): void
    {

        $result = Law::assert(Law::associative, new IntSum(5), 10, 20);
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new IntProduct(5), 10, 20);
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new StringConcat('Hello '), 'World', '!');
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new ArrayMerge([1, 2, 3]), [4, 5], [10]);
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new All(true), false, true);
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new Any(true), false, true);
        $this->assertTrue($result);
    }

    // public function testNonAssociativeCheck(): void
    // {
    //     $monoid = new class extends MonoidBase {
    //         public function id(): int
    //         {
    //             return 0;
    //         }
    //         public function op(mixed $a, mixed $b): int
    //         {
    //             return $a - $b;
    //         }
    //     };

    //     $result = Law::assert(Law::associative, $monoid, 5, 10, 20);
    //     $this->assertFalse($result, 'Subscration operation is not associative');

    //     $result = Law::assert(Law::left_identity, $monoid, 5, 10, 20);
    //     $this->assertFalse($result, 'Subscration operation is not associative');
    // }

    public function testMonoidsAsIntFoldables(): void
    {
        $numbers = IntSum::of([1, 23, 45, 187, 12]);
        $foo = $numbers->concat($numbers);
        $bar = $foo->extract();

        $this->assertTrue($bar == 268);
    }

    // public function testMonoidsAsArrayFoldables(): void
    // {
    //     $a = [[1, 2, 3], ['one', 'two', 'three'], [true, false]];
    //     $this->assertTrue(ArrayMerge::concat($a) == [1, 2, 3, 'one', 'two', 'three', true, false]);
    // }

    // public function testMonoidsAsCallables(): void
    // {
    //     $add = new IntSum();
    //     $times = new IntProduct();
    //     $composed = compose($add(5), $times(2));
    //     $this->assertTrue($composed(2) == 9);
    // }
}
