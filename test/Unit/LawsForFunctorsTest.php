<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use Closure;
use FunctionalPHP\FantasyLand\Functor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Monad\Maybe as m;
use Widmogrod\Common\PointedTrait;
use Widmogrod\Common\ValueOfTrait;
use Widmogrod\Common\ValueOfInterface;

use function FunctionalPHP\FantasyLand\compose;
use function Widmogrod\Functional\compose as FunctionalCompose;
use function Widmogrod\Functional\curry;

/**
 * Functors allow mapping a function to one or more values in a container.
 *
 * Any function or class that allows you to map a given function to one or more
 * values held in a context can be considered a functor.
 *
 * NOTE: The name of the generic used on the @template tag does determine
 * whether the correct hint gets picked up by PHPStan.
 *
 * @template a
 * @implements ValueOfInterface<a>
 * @implements Functor<a>
 * @phpstan-consistent-constructor
 *
 * @see vendor/widmogrod/php-functional/src/Monad/Identity.php
 *   Has the reference implmentation, but it lacks generics.
 * @link https://gilles.crettenand.info/blog/programming/2017/02/28/Writing-a-book
 *   Gilles Crettenand, Functional PHP
 */
class MyFunctor implements ValueOfInterface, Functor
{
    /** @use PointedTrait<a> */
    use PointedTrait;
    /** @use ValueOfTrait<a> */
    use ValueOfTrait;

    /**
     * Maps a callable that acceps and returns the class-level generic `a`.
     *
     * @param callable(a): a $function
     * @return static<a> Returns a new instance of itself.
     */
    public function map(callable $function): Functor
    {
        return new static(array_map($function, $this->value));
    }

    /**
     * @template b
     * @param b $value
     * @return b
     */
    public static function id(mixed $value): mixed
    {
        return $value;
    }
}

/**
 * An identity functor does nothing to the value besides holding it.
 *
 * Use when you store in a container a value without modifying it.
 *
 * @template a
 * @extends MyFunctor<a>
 */
class IdentityFunctor extends MyFunctor
{
    /**
     * Maps a callable that acceps class generic `a` then returns local generic `b`.
     *
     * @template b The result returned by the callable operation.
     *
     * @param callable(a): b $f
     *   Callable `$f` is invoked immediatly with `a`, returning `b` as a result.
     *
     * @return static<b>
     *   A new `static` instance containing containing `b`.
     */
    public function map(callable $f): Functor
    {
        return static::of($f($this->value));
    }
}

/**
 * Assert functor laws using native and custom constructs.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[TestDox('Assert functor laws for:')]
#[CoversNothing]
#[Group('target')]
class LawsForFunctorsTest extends TestCase
{
    #[Test]
    #[TestDox('Native constructs')]
    public function testNative(): void
    {
        $data = [1, 2, 3, 4];

        $id = fn ($id) => $id;
        $add2 = fn ($a) => $a + 2;
        $times10 = fn ($a) => $a * 10;
        $composed = fn ($a) => $add2($times10($a));

        $lawr1 = array_map($id, $data);
        $lawr2 = $id($data);
        $this->assertTrue($lawr1 == $lawr2, 'First functor law');

        $r1 = array_map($add2, array_map($times10, $data));
        $r2 = array_map($composed, $data);
        $this->assertTrue($r1 === $r2, 'Second functor law');
    }

    #[Test]
    public function testCustomClass(): void
    {
        $data = [1, 2, 3, 4];
        $f = fn ($a) => $a + 2;
        $g = fn ($a) => $a * 10;

        // First functor law.
        // map(id) === id
        // identity dumps the correct type.
        $law1r1 = array_map([MyFunctor::class, 'id'], $data);
        $law1r2 = MyFunctor::id($data);
        $this->assertTrue($law1r1 == $law1r2, 'First law using custom functor and external data');

        $hello = MyFunctor::of($data);
        $law1r3 = array_map([MyFunctor::class, 'id'], $hello->extract());
        $law1r4 = MyFunctor::id($hello->extract());
        $this->assertTrue($law1r3 == $law1r4, 'First law using custom functor and internal data');

        // Second law, using  native constructs.
        // compose(map(f), map(g)) == map(compose(f,g))
        $left = compose(
            fn ($a) => array_map($f, $a),
            fn ($a) => array_map($g, $a)
        );
        $right = fn ($a) => array_map(compose($f, $g), $a);
        $this->assertTrue($left($data) === $right($data));

        // Second law, functor class on the left, native constructs on the right.
        // compose(map(f), map(g)) == map(compose(f,g))
        $map = fn ($a, $op) => MyFunctor::of($a)->map($op)->extract();
        $left2 = compose(
            fn ($a) => $map($a, $f),
            fn ($a) => $map($a, $g)
        );
        $this->assertTrue($left2($data) === $right($data));

        // Second law, functor class both on the left and right hands.
        // The left hand has two map operations.
        // The right hand has only one map operation.
        // compose(map(f), map(g)) == map(compose(f,g))
        $right2 = MyFunctor::of($data)->map(compose($f, $g))->extract();
        $this->assertTrue($left2($data) === $right2);
    }

    public function testMaybeMonad(): void
    {
        // map(id) === id
        $id = fn ($value) => $value;
        $j = m\Just(10);
        $n = m\Nothing();
        $this->assertTrue($j->map($id) == $id($j));
        $this->assertTrue($n->map($id) === $id($n));

        // compose(map(f), map(g)) == map(compose(f,g))
        $f = fn ($a) => $a * 10;
        $g = fn ($a) => $a + 2;
        $composed = fn ($a) => $g($f($a));
        $this->assertTrue($j->map($f)->map($g) == $j->map($composed));
        $this->assertTrue($n->map($f)->map($g) == $n->map($composed));
    }

    public function testIdentityFunctor(): void
    {
        $add = curry(fn ($a, $b) => $a + $b);
        $functor = new IdentityFunctor(5);

        $partial = $functor->map($add);
        $this->assertTrue($partial->extract() instanceof Closure, 'Functor contains partially applied function');
        $this->assertTrue($partial->extract()(10) === 15, 'Apply the partial function directly');

        $a = fn (callable|Closure $g) => $g(15);
        $this->assertTrue($partial->map($a)->extract() === 20, 'Apply the partial using map');

        // map(id) === id
        $id = fn ($value) => $value;
        $this->assertTrue($partial->map($id) == $id($partial));

        // compose(map(f), map(g)) == map(compose(f,g))
        $a = IdentityFunctor::of(1);
        $b = $a->extract();

        $f = fn (int $a): int => $a + 2;
        $g = fn (int $a): int => $a * 10;

        $e = IdentityFunctor::of(3)->map($f)->map($g)->extract();
        $d = IdentityFunctor::of(3)->map(compose($g, $f))->extract();
        $this->assertTrue($e === $d);
    }
}
