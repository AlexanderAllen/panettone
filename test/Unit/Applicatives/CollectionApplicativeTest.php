<?php

declare(strict_types=1);

namespace AlexanderAllen\Panettone\Test\Unit\Applicative;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversNothing, DataProvider, Group, Test, TestDox};
use ArrayIterator;
use FunctionalPHP\FantasyLand\Apply;
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
    public static function pure($values): static
    {
        if ($values instanceof Traversable) {
            $values = iterator_to_array($values);
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        return new static($values);
    }

    /**
     * @param Applicative<a> $data
     * @return static<a>
     */
    public function apply(Applicative $data): static
    {
        $r = fn ($acc, callable $function) => array_merge(
            $acc,
            array_map($function, $data->value)
        );
        return $this->pure(array_reduce($this->value, $r, []));
    }

    /**
     * @param static<a> $data
     * @return Applicative<a>
     */
    public function ap(Apply $data): Apply
    {
        $r = fn ($acc, callable $function) => array_merge(
            $acc,
            array_map($function, $data->value)
        );
        return $this->pure(array_reduce($this->value, $r, []));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->value);
    }
}

#[TestDox('Collection Applicative')]
#[CoversNothing]
#[Group('target')]
class CollectionApplicativeTest extends TestCase
{
    /**
     * The applicative contains a function list that is applied to an int array.
     */
    public function testSimpleApplicative(): void
    {
        $a = CollectionApplicative::pure([
            fn ($a) => $a * 2,
            fn ($a) => $a + 10
        ]);

        $b = $a->apply(CollectionApplicative::pure([1, 2, 3]));
        $r = iterator_to_array($b);
        $this->assertTrue($r == [2, 4, 6, 11, 12, 13]);

        Law::test($this, $a, 3);
    }

    public function testImageGalleryApplicative(): void
    {
        $size = fn ($i) => $i;
        $thumbnail = fn ($i) => $i . '_tn';
        $mobile = fn ($i) => $i . '_small';

        $images = CollectionApplicative::pure(['one', 'two', 'three']);
        $process = CollectionApplicative::pure([
            $size, $thumbnail, $mobile
        ]);

        $t = $process->apply($images);
        $this->assertTrue(iterator_to_array($t) == ['one', 'two', 'three', 'one_tn', 'two_tn', 'three_tn', 'one_small', 'two_small', 'three_small']);
    }
}
