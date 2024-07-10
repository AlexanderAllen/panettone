<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, Group, Test, TestDox};

use function PHPStan\dumpType;

/**
 * How to deploy PHPStan generics when using traits.
 */
#[TestDox('Isolate generic return type')]
#[CoversNothing]
#[Group('target')]
class GenericTraits2Test extends TestCase
{
    #[Test]
    public function testGenericHintUsingConstructor(): void
    {
        $b = new TraitConsumerOf(3);
        $c = $b->extract();
        $this->assertTrue($c === 3);

        // Instantiation using new dumps correct hints.
        $e = new TraitConsumerOf('Hello');
        $x = $e->extract();
        $this->assertTrue($x === 'Hello');
    }

    #[Test]
    public function testGenericsUsingStatic(): void
    {
        // Using self is still giving mixed, but now without errors.
        $a = TraitConsumerOf::of(1);
        $b = $a->extract(); // mixed here too, that's unnaceptable.
        // dumpType($f); // dumped type is mixed
        $this->assertTrue($b === 1);

        $c = TraitConsumerOf::nonLocalGeneric(2);
        $this->assertTrue($c->extract() == 2);
    }
}

/**
 * When implementing the interface:
 * dumpType() gives the correct hints for TraitConsumerOf<int> or TraitConsumerOf<string>
 * However, PHPStan on the IDE reports TraitConsumerOf<mixed>  :'(
 *
 * Removing the ConsistentConstructorOf interface restores the correct generic functionality
 * to the hints, but then I get hit with "Unsafe usage of new static()"
 *
 * SOLVED: Using the other alternatives mentioned by PHPStan docs does solve the generic
 * loss, with plenty of approaches supported for different scenarios.
 *
 * I'm sticking with the `consistent-constructor` tag because it supports the more open ended inheritance case.
 * But for more locked down inheritance models there's also solutions for that (such as final constructors).
 *
 * @link @see https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static
 * @link https://github.com/phpstan/phpstan/discussions/11302 My final response to this issue.
 * @link https://phpstan.org/r/7a4a4edb-a2f1-467a-bcef-4037ce45f6c9 The cnstructor tag supports child overloading!
 * @link https://drupal.slack.com/archives/C033S2JUMLJ/p1720436243502369
 *   Drupal.org chit-chat about the interface edge case losing generic typing with @AndyF, @AlexanderAllen
 * @link https://www.drupal.org/docs/develop/development-tools/phpstan/handling-unsafe-usage-of-new-static
 *   Updated dox on DO for this edge case.
 *
 * @phpstan-consistent-constructor
 * @template IdentityValue
 */
class TraitConsumerOf
{
    /**
     * @var IdentityValue
     */
    protected $value;

    /**
     * Ensure everything on start.
     *
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

    /**
     * Generic is retained through static constructor.
     *
     * @template T
     * @param T $value
     * @return static<T>
     */
    public static function of(mixed $value)
    {
        return new static($value);
    }

    /**
     * Generic type is lost using a non-local (class) generic, reverts to mixed.
     *
     * @param IdentityValue $value
     * @return static<IdentityValue>
     */
    public static function nonLocalGeneric(mixed $value)
    {
        return new static($value);
    }
}
