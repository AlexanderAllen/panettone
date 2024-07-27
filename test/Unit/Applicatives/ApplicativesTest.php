<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Applicative;

use Closure;
use FunctionalPHP\FantasyLand\Apply;
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
 * @implements Apply<a>
 * @implements PointedInterface<a>
 */
abstract class Applicative implements Apply, PointedInterface
{
    /** @use PointedTrait<a> */
    use PointedTrait;

    /**
     * Values that cannot be modified directly are considered pure.
     * Pure is used to create a new applicative from any callable.
     *
     * Use local generics on static functions.
     *
     * Binding signature return to interface instead of abastract.
     * The interface inherits Functor while this abstract class does not.
     * The PHPDoc return must be set to `static` to prevent compilation issues
     * downstream.
     *
     * @template b
     * @param b $value
     * @return static
     */
    public static function pure($value): static
    {
        return new static($value);
    }

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
     * @todo IDK how TF express what apply does inside in stan.
     *
     * @param Applicative<a> $f
     * @return Applicative<a>
     */
    abstract public function apply(Applicative $f): Applicative;

    abstract public function ap(Apply $b): Apply;

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
     * Maps `f` to an applicative of `a` then applies it to `self`.s
     *
     * @param callable(a): static<a> $function
     *   Callable `f` is invoked immediatly with `a`.
     *
     * @return static<a>
     *   A new instance of `static`.
     */
    public function map(callable $function): Apply
    {
        return $this->pure($function)->apply($this);
    }

    /**
     * @return a
     */
    public function get(): mixed
    {
        return $this->value;
    }
}

/**
 * @template a
 * @extends Applicative<a>
 */
class IdentityApplicative extends Applicative
{
    /**
     * @param Applicative<a> $f
     * @return Applicative<a>
     */
    public function apply(Applicative $f): Applicative
    {
        return static::pure($this->get()($f->get()));
    }

    /**
     * @param Applicative<a> $f
     * @return Applicative<a>
     */
    public function ap(Apply $f): Apply
    {
        return static::pure($this->get()($f->get()));
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
