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
 * How to deploy PHPStan generics when using traits.
 *
 * This dead simple example demonstrates that the type information is not lost
 * when using generics through traits.
 *
 * You can asert with PHPStan\dumpType, but that requires phpstan installed via composer, which I'm not doing.
 * If adding PHPStan via composer, include the following:
 *
 * ```
 * use function PHPStan\dumpType;
 * dumpType($c);
 * ```
 *
 * @package AlexanderAllen\Panettone\Test
 *
 * @link https://phpstan.org/r/a5d29122-fd07-4f3e-bce6-04ed40ad63a3 How to use dumpType
 * @link https://github.com/phpstan/phpstan/issues/4069 How to use dumpType
 * @link https://phpstan.org/user-guide/troubleshooting-types mentions dumpType()
 */
#[TestDox('Using traits with PHPStan generics')]
#[CoversNothing]
#[Group('target')]
class GenericTraitsTest extends TestCase
{
    #[Test]
    public function testGenericContainers(): void
    {
        // Generic variable is properly hinted at!
        // GenericValueContainer<int>
        $a = new GenericValueContainer(5);
        $this->assertTrue($a->extract() === 5);

        $b = new TraitConsumer(3);
        $c = $b->extract();
        $this->assertTrue($c === 3);
    }
}

 /**
  * Can you get PHPStan to hint at the contained value during extraction?
  *
  * @template IdentityValue The identity contained inside the functor.
  */
class GenericValueContainer
{
    /**
     * @var IdentityValue
     */
    public mixed $value;

    /**
     * @param IdentityValue $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return IdentityValue
     */
    public function extract()
    {
        return $this->value;
    }
}

/**
 * @template IdentityValue The identity contained inside the functor.
 */
trait GenericPointedTrait
{
    /**
     * @var IdentityValue
     */
    protected mixed $value;

    /**
     * Ensure everything on start.
     *
     * @param IdentityValue $value
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * @return IdentityValue
     */
    public function extract()
    {
        return $this->value;
    }
}

/**
 * How to consume a trait that contains PHPStan generics.
 *
 * @link https://github.com/phpstan/phpstan/issues/9630 Begin the rabbit hole.
 * @link https://github.com/phpstan/phpstan/issues/11160 Follow the rabbit hole.
 * @link https://phpstan.org/r/af42e09b-2d4a-4bac-b837-e6c3e34b8ab9 Rabbit's dead: types without generics.
 * @link https://phpstan.org/r/077a11ae-e527-4dc1-8f91-975267d73643 Rabbit's deader: Types WITH generics.
 * @link https://phpstan.org/writing-php-code/phpdoc-types#offset-access
 *   The only reference for @use and traits I could find.
 *
 * @template IdentityValue
 */
class TraitConsumer
{
    /** @use GenericPointedTrait<IdentityValue> */
    use GenericPointedTrait;

    public function foo(): mixed
    {
        return $this->extract();
    }
}
