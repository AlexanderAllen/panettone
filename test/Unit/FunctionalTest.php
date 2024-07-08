<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Apply;
use FunctionalPHP\FantasyLand\Functor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversNothing, Group, Test, TestDox, Depends, UsesClass};
use Widmogrod\Common\PointedTrait;
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
    use PointedTrait;

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
     * @return a
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * Override the trait dox.
     *
     * @param a $value
     *
     * @return StateMonad<a>
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
#[CoversNothing]
class FunctionalTest extends TestCase
{
    #[Test]
    #[TestDox('Use literal book example')]
    #[Group('target')]
    public function testBasics(): void
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
        $this->assertIsInt($result[0]);
    }

    #[Group('target')]
    #[TestDox('Change to string state')]
    public function testGiant(): void
    {
        $giantOp = function (string $state1 = '') {
            $new = strtoupper($state1);
            return [$state1, $new];
        };
        // The monad stores the operation, but the state function stores the value (state).
        $mstate = fn (callable $stateFunction): StateMonad =>
            StateMonad::of(function ($state3) use ($stateFunction) {
                return $stateFunction($state3);
            });
        $giantStr = $mstate($giantOp);
        $result = $giantStr->get()('Hello Ritchie');
        $this->assertEquals('HELLO RITCHIE', $result[1]);
        // $final = $state->runState('Richard');
        // $t = evalState($state, 'Richard');
    }

    #[Group('ignore')]
    public function testApplicatives(): void
    {
        $giantOp = function (string $state1 = '') {
            $new = strtoupper($state1);
            // state, value
            return [$state1, $new];
        };

        $randomOp = function (string $state2 = '') {
            $new = str_shuffle($state2);
            return [$state2, $new];
        };

        // The monad stores the operation, but the state function stores the value (state).
        $mstate = fn (callable $stateFunction): StateMonad =>
            StateMonad::of(function ($state3) use ($stateFunction) {
                return $stateFunction($state3);
            });
        $giantStr = $mstate($giantOp);
        $result = $giantStr->get()('Hello Ritchie');
        $this->assertEquals('HELLO RITCHIE', $result[1]);

        $randomStr = $mstate($giantOp);
        $app = $randomStr->ap($giantStr);
        $r1 = $app->get();
        $r1(fn ($foo = '') => 'bar');

        $test = null;

        // $final = $state->runState('Richard');
        // $t = evalState($state, 'Richard');
    }
}
