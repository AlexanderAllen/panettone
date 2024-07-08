<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use FunctionalPHP\FantasyLand\Apply as ApplyInterface;
use FunctionalPHP\FantasyLand\Chain;
use FunctionalPHP\FantasyLand\Functor as FantasyFunctor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};
use Widmogrod\Common\PointedTrait;
use Widmogrod\Common\ValueOfTrait;

/**
 * Apply PHPStan generic patterns to functional patterns.
 *
 * @package AlexanderAllen\Panettone\Test
 */
#[TestDox('PHPStan generic patterns')]
#[CoversNothing]
#[Group('ignore')]
class ApplicativesTest extends TestCase
{
    #[Test]
    #[TestDox('Native constructs')]
    public function testFoo(): void
    {
        $add2 = fn ($a) => $a + 2;
        $a = TestFunctorB::of(5);
        $b = $a->map($add2);
        $this->assertTrue($b instanceof TestFunctorB);
        $this->assertTrue($b->extract() == 7);

        $c = TestStaticFunctor::of(5)->mapStatic($add2);
        $this->assertTrue($c instanceof TestStaticFunctor);
        $this->assertTrue($c->extract() == 7);
    }
}

/**
 * @template a The value inherited from the Apply interface.
 * @template b The value from the Chain interface.
 * @implements ApplyInterface<a>
 * @implements Chain<b>
 */
class ApplicativeTest implements ApplyInterface, Chain
{
    use PointedTrait;

    public function ap(ApplyInterface $applicable): ApplyInterface
    {
        if (! $applicable instanceof self) {
            throw new \LogicException(sprintf('Applicative must be an instance of %s', self::class));
        }
        return $applicable->bind(function (callable $f) {
            return self::of($f($this->value));
        });
    }

    public function bind(callable $transformation)
    {
        return $transformation($this->value);
    }

    /**
     * @template TReturnValue3 of ApplicativeTest
     * @param callable(a): TReturnValue3 $f
     * @return TReturnValue3 Returns a new instance of itself.
     */
    public function map3(callable $f): ApplicativeTest
    {
        $s = new self($f($this->value));
        return $s;
    }

    public function map(callable $function): FantasyFunctor
    {
        return $function();
    }
}
