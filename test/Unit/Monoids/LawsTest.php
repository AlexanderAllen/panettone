<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Monoids;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, TestDox};
use RuntimeException;

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
        Monoid $m,
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
 * associated identity element.
 *
 * `id` and `op` methods are abstract as those are implementation-specific.
 *
 * An example use case for monoids is folding a collection of values with the
 * same type as the monoid class.
 */
interface Monoid
{
    public static function id(): mixed;
    public static function op(mixed $a, mixed $b): mixed;
    /**
     * @param array<mixed> $values
     */
    public static function concat(array $values): mixed;
}

abstract class MonoidBase implements Monoid
{
    abstract public static function id(): mixed;
    abstract public static function op(mixed $a, mixed $b): mixed;
    public static function concat(array $values): mixed
    {
        $class = get_called_class();
        return array_reduce($values, [$class, 'op'], [$class, 'id']());
    }
    public function __invoke(mixed ...$args): mixed
    {
        switch (count($args)) {
            case 0:
                throw new RuntimeException('Expects at least 1 parameter.');
            case 1:
                return function ($b) use ($args) {
                    return static::op($args[0], $b);
                };
            default:
                return static::concat($args);
        }
    }
}

class IntSum extends MonoidBase
{
    public static function id(): int
    {
        return 0;
    }
    public static function op(mixed $a, mixed $b): mixed
    {
        return $a + $b;
    }
}

class IntProduct extends MonoidBase
{
    public static function id(): int
    {
        return 1;
    }
    public static function op(mixed $a, mixed $b): mixed
    {
        return $a * $b;
    }
}

class StringConcat extends MonoidBase
{
    public static function id(): string
    {
        return '';
    }
    public static function op(mixed $a, mixed $b): mixed
    {
        return $a . $b;
    }
}

class ArrayMerge extends MonoidBase
{
    public static function id(): mixed
    {
        return [];
    }
    public static function op(mixed $a, mixed $b): mixed
    {
        return array_merge($a, $b);
    }
}

class Any extends MonoidBase
{
    public static function id(): bool
    {
        return false;
    }
    public static function op(mixed $a, mixed $b): bool
    {
        return $a || $b;
    }
}

class All extends MonoidBase
{
    public static function id(): bool
    {
        return true;
    }
    public static function op(mixed $a, mixed $b): bool
    {
        return $a && $b;
    }
}

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
        $result = Law::assert(Law::left_identity, new IntSum(), 5, 10, 20);
        $this->assertTrue($result);
        $result = Law::assert(Law::right_identity, new IntSum(), 5, 10, 20);
        $this->assertTrue($result);

        $result = Law::assert(Law::left_identity, new IntProduct(), 5, 10, 20);
        $this->assertTrue($result);
        $result = Law::assert(Law::right_identity, new IntProduct(), 5, 10, 20);
        $this->assertTrue($result);

        $result = Law::assert(Law::left_identity, new StringConcat(), 'Hello ', 'World', '!');
        $this->assertTrue($result);
        $result = Law::assert(Law::right_identity, new StringConcat(), 'Hello ', 'World', '!');
        $this->assertTrue($result);

        $result = Law::assert(Law::left_identity, new Any(), true, false, true);
        $this->assertTrue($result);
        $result = Law::assert(Law::right_identity, new Any(), true, false, true);
        $this->assertTrue($result);

        $result = Law::assert(Law::left_identity, new All(), true, false, true);
        $this->assertTrue($result);
        $result = Law::assert(Law::right_identity, new All(), true, false, true);
        $this->assertTrue($result);
    }

    public function testAssociativity(): void
    {
        $result = Law::assert(Law::associative, new IntSum(), 5, 10, 20);
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new IntProduct(), 5, 10, 20);
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new StringConcat(), 'Hello ', 'World', '!');
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new ArrayMerge(), [1, 2, 3], [4, 5], [10]);
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new All(), true, false, true);
        $this->assertTrue($result);

        $result = Law::assert(Law::associative, new Any(), true, false, true);
        $this->assertTrue($result);
    }

    public function testNonAssociativeCheck(): void
    {
        $monoid = new class extends MonoidBase {
            public static function id(): int
            {
                return 0;
            }
            public static function op(mixed $a, mixed $b): int
            {
                return $a - $b;
            }
        };

        $result = Law::assert(Law::associative, $monoid, 5, 10, 20);
        $this->assertFalse($result, 'Subscration operation is not associative');

        $result = Law::assert(Law::left_identity, $monoid, 5, 10, 20);
        $this->assertFalse($result, 'Subscration operation is not associative');
    }

    public function testMonoidsAsIntFoldables(): void
    {
        $numbers = [1, 23, 45, 187, 12];
        $this->assertTrue(IntSum::concat($numbers) == 268);
    }

    public function testMonoidsAsArrayFoldables(): void
    {
        $a = [[1, 2, 3], ['one', 'two', 'three'], [true, false]];
        $this->assertTrue(ArrayMerge::concat($a) == [1, 2, 3, 'one', 'two', 'three', true, false]);
    }

    public function testMonoidsAsCallables(): void
    {
        $add = new IntSum();
        $times = new IntProduct();
        $composed = compose($add(5), $times(2));
        $this->assertTrue($composed(2) == 9);
    }
}
