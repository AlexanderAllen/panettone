<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use Closure;
use FunctionalPHP\FantasyLand\Functor as FantasyFunctor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Common\PointedTrait;
use Widmogrod\Common\ValueOfInterface;
use Widmogrod\Monad\Maybe as m;

use function FunctionalPHP\FantasyLand\compose;
use function Widmogrod\Functional\curry;

/**
 * @template a
 */
trait GenericPointedTrait
{
    /**
     * @var a
     */
    protected $value;

    /**
     * Ensure everything on start.
     *
     * @param a $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @template b
     * @param b $value
     * @return static<b>
     */
    public static function of($value)
    {
        return new static($value);
    }
}

/**
 * @template a
 */
interface GenericFunctor
{
    /**
     * map :: Functor f => (a -> b) -> f b
     *
     * @template b
     * @param callable(a): b $function
     * @return static
     */
    public function map(callable $function): static;
}

/**
 * Functors allow mapping a function to one or more values in a container.
 *
 * @template IdentityValue
 * @phpstan-consistent-constructor
 */
class Functor implements ValueOfInterface
{
    /** @use GenericPointedTrait<IdentityValue> */
    use GenericPointedTrait;

    /**
     * @template T
     * @param callable(IdentityValue): T $function
     * @return T Returns a new instance of itself.
     */
    public function map(callable $function)
    {
        return new static(array_map($function, $this->value));
    }

    /**
     * @return IdentityValue
     */
    public function extract()
    {
        return $this->value;
    }

    /**
     * @template identity
     * @param identity $value
     * @return identity
     */
    public static function id(mixed $value): mixed
    {
        return $value;
    }
}

/**
 * An identity functor does nothing to the value besides holding it.
 *
 * @template IdentityValue
 * @extends Functor<IdentityValue>
 */
class IdentityFunctor extends Functor
{
    /**
     * @template T
     * @param callable(T): static $f
     * @return static<IdentityValue>
     */
    public function map(callable $f): FantasyFunctor
    {
        return new static($f($this->value));
    }
}

/**
 * Assert functor laws using native and custom constructs.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[TestDox('Assert functor laws for:')]
#[CoversNothing]
#[Group('ignore')]
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
        $law1r1 = array_map([Functor::class, 'id'], $data);
        $law1r2 = Functor::id($data);
        $this->assertTrue($law1r1 == $law1r2, 'First law using custom functor and external data');

        $hello = Functor::of($data);
        $law1r3 = array_map([Functor::class, 'id'], $hello->extract());
        $law1r4 = Functor::id($hello->extract());
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
        $map = fn ($a, $op) => Functor::of($a)->map($op)->extract();
        $left2 = compose(
            fn ($a) => $map($a, $f),
            fn ($a) => $map($a, $g)
        );
        $this->assertTrue($left2($data) === $right($data));

        // Second law, functor class both on the left and right hands.
        // The left hand has two map operations.
        // The right hand has only one map operation.
        // compose(map(f), map(g)) == map(compose(f,g))
        $right2 = Functor::of($data)->map(compose($f, $g))->extract();
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

        $f = fn (callable $g) => $g(15);
        $this->assertTrue($partial->map($f)->extract() === 20, 'Apply the partial using map');

        // map(id) === id
        $id = fn ($value) => $value;
        $this->assertTrue($partial->map($id) == $id($partial));

        // compose(map(f), map(g)) == map(compose(f,g))
        [$f, $g] = [fn ($a) => $a * 10, fn ($a) => $a + 2];
        $this->assertTrue($functor->map($f)->map($g) == $functor->map(fn ($a) => $g($f($a))));
    }
}
