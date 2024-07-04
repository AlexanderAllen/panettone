<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Apply;
use FunctionalPHP\FantasyLand\Functor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Group, Test, TestDox, Depends, UsesClass};
use Widmogrod\Monad\State as s;
use Widmogrod\Monad\State;

use function Symfony\Component\String\u;
use function Widmogrod\Monad\State\evalState;
use function Widmogrod\Monad\State\runState;
use function Widmogrod\Monad\State\state;

/**
 * @template a
 * @implements Apply<a>
 */
class Applicative implements Apply
{
    /**
     *
     * @var a
     */
    public mixed $value;

    /**
     * Stub.
     *
     * @param mixed $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     *
     * @param Apply<a> $b
     * @return Apply<a>
     */
    public function ap(Apply $b): Apply
    {
        $test = null;
        $args = func_get_args();
        return state(fn ($b) => strtoupper($b));
    }

    public function map(callable $function): Functor
    {
        return new State($function($this->value));
    }
}

/**
 * @template a
 * @extends State<a>
 * @template-inherit Pointed<a>
 */
class StateMonad extends State
{
    /**
     * Stub.
     *
     * @return a Something.
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * Override the trait dox.
     *
     * @template b
     *
     * @param b $value
     *
     * @return StateMonad<b>
     */
    public static function of($value)
    {
        return new self($value);
    }
}


/**
 * Test suite for nette generators.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[TestDox('Functional tests')]
class FunctionalTest extends TestCase
{
    #[Test]
    #[TestDox('test')]
    #[Group('target')]
    public function testSomething(): void
    {
        $sf = function ($state) {
            mt_srand($state);
            return [mt_rand(), mt_rand()];
        };

        /**
        * Creates state monad.
        *
        * The monad binds an operation at creation time, but without executing it.
        * As an applicative it represents the application, but not the value.
        *
        * @param callable $stateFunction
        *   The state is encapsulated inside the state function.
        */
        $mstate = fn (callable $stateFunction): StateMonad =>
            StateMonad::of(function ($state) use ($stateFunction) {
                return $stateFunction($state);
            });
        $randomInt = $mstate($sf);
        $result = $randomInt->get()(12345);



        // strtoupper()
        // $final = $state->runState('Richard');
        // $t = evalState($state, 'Richard');
    }
}
