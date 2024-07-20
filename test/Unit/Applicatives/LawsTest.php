<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Applicative;

use Closure;
use FunctionalPHP\FantasyLand\Functor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};

use function FunctionalPHP\FantasyLand\compose;
use function Widmogrod\Functional\curry;

/**
 * Laws for applicative functors.
 *
 * - Closed under composition: will return new applicative of the same type.
 * - Apply takes an applicative of the same type as the apply class.
 * - Book: we cannot enforce with PHP type system; Me: should no longer apply.
 */
#[TestDox('Laws for Applicatives')]
#[CoversNothing]
#[Group('target')]
class LawsTest extends TestCase
{
    /**
     * @template a
     * @param Applicative<Closure(a): a> $f1
     * @param callable $f2
     * @param mixed $x
     * @return array<string, bool>
     *
     * @todo From the book: "We cannot ensure the first function return type matches
     * the second function first parameter type"
     */
    public function laws(Applicative $f1, callable $f2, mixed $x): array
    {
        $identity = fn ($x) => $x;
        $compose = fn (callable $a) => fn (callable $b) => fn ($x) => $a($b($x));
        $pure_x = $f1->pure($x);
        $pure_f2 = $f1->pure($f2);

        return [
            'identity' => $f1->pure($identity)->apply($pure_x) == $pure_x,
            'homomorphism' => $f1->pure($f2)->apply($pure_x) == $f1->pure($f2($x)),
            'interchange' => $f1->apply($pure_x) == $f1->pure(fn ($f) => $f($x))->apply($f1),
            'composition' => $f1->pure($compose)->apply($f1)->apply($pure_f2)->apply($pure_x) ==
                $f1->apply($pure_f2->apply($pure_x)),
            'map' => $pure_f2->apply($pure_x) == $pure_x->map($f2),
        ];
    }

    /**
     * pure(f)->apply == map(f)
     *
     * Applicatives can be used anywhere a functors are used with map.
     */
    #[Test]
    public function testBasics(): void
    {
        $add = curry(fn (int $a, int $b): int => $a + $b);
        $five = IdentityApplicative::pure(5);
        $ten = IdentityApplicative::pure(10);
        $applicative = IdentityApplicative::pure($add);
        $result = $applicative->apply($five)->apply($ten)->get();
        $this->assertTrue($result === 15);
    }

    /**
     * pure(f)->apply == map(f)
     *
     * Applicatives can be used anywhere a functors are used with map.
     */
    #[Test]
    public function testMap(): void
    {
        $a = IdentityApplicative::pure('strtoupper');
        $result = $this->laws($a, 'trim', ' Hello Waldo! ');

    }

    /**
     * pure(id)->apply(x) == id(x)
     *
     * Applying the identity function results in no change to the value.
     *
     * This law asserts that the apply method only applies the given function
     * without hidden effects or transformations.
     */
    #[Group('ignore')]
    public function testIdentity(): void
    {
    }

    /**
     * pure(f)->apply(x) == pure(f(x))
     *
     * Creating an applicative functor and applying it to the value has the
     * same effect as first calling the function on the value then placing the
     * result in a functor.
     *
     * This law asserts that applicatives can be created anytime instead of
     * having to put functions within a context immediatly, and helps in the
     * implementation of curryied instead of unary functions.
     *
     * @todo link to unary vs curryied functions
     * @todo link to homomorphism (wikipedia)
     */
    #[Group('ignore')]
    public function testHomomorphism(): void
    {
    }

    /**
     * pure(f)->apply(x) == pure(fn (f) => f(x))->apply(f)
     *
     * Appliying a function on a value is the same as creating an applicative
     * functor wiht a lifted value and applying it to the function.
     *
     * A lifted value is a closure for the value that will call the target
     * function on it.
     *
     * This law asserts that the pure function performs no modifications
     * beyond wrapping the given value.
     */
    #[Group('ignore')]
    public function testInterchange(): void
    {
    }

    /**
     * pure(compose)->apply(f1)->apply(f2)->apply(x) ==
     * pure(f1)->apply(pure(f2)->apply(x)), or
     * pure(compose(f1, f2))->apply(x) == ...
     *
     * Asserts that you can apply a composed version of two functions to the
     * value, or call them separately.
     */
    #[Group('ignore')]
    public function testComposition(): void
    {
    }
}
