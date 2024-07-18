<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Applicative;

use Closure;
use FunctionalPHP\FantasyLand\Functor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Monad\Maybe as m;
use Widmogrod\Common\PointedTrait;
use Widmogrod\Common\ValueOfTrait;
use Widmogrod\Common\ValueOfInterface;

use function FunctionalPHP\FantasyLand\compose;
use function Widmogrod\Functional\curry;

/**
 * @template a
 * @implements Functor<a>
 */
abstract class Applicative implements Functor
{
    /**
     * Values that cannot be modified directly are considered pure.
     * Pure is used to create a new applicative from any callable.
     *
     * Use local generics on static functions.
     *
     * @template b
     * @param b $value
     * @return Applicative<b>
     */
    abstract public static function pure($value): Applicative;

    /**
     * Applies the stored function to the given parameter.
     *
     * The parameter must be of the same type so that the implemenation knows
     * how to access the inner value.
     *
     * The book says "PHP types do not allow enforcement of the above rule, and
     * so we must resign ourselves to using Applicative".
     *
     * PHPStan says, hold my types.
     *
     * @param Applicative<a> $f
     * @return Applicative<a>
     */
    abstract public function apply(Applicative $f): Applicative;

    /**
     * map :: Functor f => (a -> b) -> f b
     *
     * Encapsulates the `callable` in a applicative using the `pure` method,
     * then applies the applicative to the actual value.
     *
     * Book: "we have the same issue for map that has to keep the return type
     * as Functor, as PHP does not support return type covariance, if it did
     * we could specify a more specialized type (a child type) as the return
     * value".
     *
     * I believe the book predates PHP 7.4.0
     *
     * @see https://www.php.net/ChangeLog-7.php#7.4.0 Releae notes.
     * @see https://www.php.net/releases/7_4_0.php Announcement
     * @see https://www.php.net/manual/en/language.oop5.variance.php Co- and Contra-variance.
     *
     * Maps a callable that acceps class generic `a` then returns local generic `b`.
     *
     * @template b The result returned by the callable operation.
     *
     * @param callable(a): b $function
     *   Callable `$f` is invoked immediatly with `a`, returning `b` as a result.
     *
     * @return static<callable(a): b>
     *   A new `static` instance containing containing `b`.
     */
    public function map(callable $function): Functor
    {
        return $this->pure($function)->apply($this);
    }
}

class IdentityApplicative extends Applicative
{
    private $value;
    protected function __construct($value)
    {
        $this->value = $value;
    }
    public static function pure($value): Applicative
    {
        return new static($value);
    }
    public function apply(Applicative $f): Applicative
    {
        return static::pure($this->get()($f->get()));
    }
    public function get(): mixed
    {
        return $this->value;
    }
}

/**
 * Assert functor laws using native and custom constructs.
 */
#[TestDox('Applicatives test')]
#[CoversNothing]
#[Group('target')]
class ApplicativesTest extends TestCase
{
    /**
     * Applicative functors apply functors to other functors.
     *
     * In this case, apply a functor containing a function to another functor
     * containing an int value.
     *
     * This example uses the IdentityFunctor class as a curryied function holder.
     */
    #[Test]
    public function testFullApplicatives(): void
    {
        $add = curry(fn (int $a, int $b): int => $a + $b);
        $five = IdentityApplicative::pure(5);
        $ten = IdentityApplicative::pure(10);
        $applicative = IdentityApplicative::pure($add);
        $result = $applicative->apply($five)->apply($ten)->get();
        $this->assertTrue($result === 15);
    }
}
