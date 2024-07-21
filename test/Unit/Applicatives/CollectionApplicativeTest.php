<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Applicative;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, DataProvider, Group, Test, TestDox};
use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @template a
 * @template TKey
 * @template TValue
 * @extends Applicative<a>
 * @implements IteratorAggregate<TKey, TValue>
 * @phpstan-consistent-constructor
 */
class CollectionApplicative extends Applicative implements IteratorAggregate
{
    /**
     * @var a $values
     */
    private $values;

    /**
     * @param a $values
     */
    protected function __construct($values)
    {
        $this->values = $values;
    }

    public static function pure($values): Applicative
    {
        if ($values instanceof Traversable) {
            $values = iterator_to_array($values);
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        return new static($values);
    }

    public function apply(Applicative $data): Applicative
    {
        $r = fn ($acc, callable $function) => array_merge(
            $acc,
            array_map($function, $data->values)
        );
        return $this->pure(array_reduce($this->values, $r, []));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }
}

#[TestDox('Collection Applicative')]
#[CoversNothing]
#[Group('target')]
class CollectionApplicativeTest extends TestCase
{
    public mixed $a;

    public function setup(): void
    {
        $this->a = CollectionApplicative::pure([
            fn ($a) => $a * 2,
            fn ($a) => $a + 10
        ]);
    }

    public function testApplication(): void
    {
        $b = $this->a->apply(CollectionApplicative::pure([1, 2, 3]));
        $r = iterator_to_array($b);
        $this->assertTrue($r == [2, 4, 6, 11, 12, 13]);

        $cases = Law::cases();
        array_walk($cases, fn (Law $case) => Law::assert($case, $this->a, fn ($a) => $a * 2, 3));
    }
}
