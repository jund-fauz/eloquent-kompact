<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

use function JundFauz\EloquentKompact\collect;

class CollectionTest extends TestCase
{
    #[Test]
    public function it_filters_out_blank_values(): void
    {
        $collection = new Collection(['a', '', '  ', null, 0, false, [], 'k' => 'v']);
        $filtered = $collection->filterFilled();

        $this->assertInstanceOf(Collection::class, $filtered);
        $this->assertSame([0 => 'a', 4 => 0, 5 => false, 'k' => 'v'], $filtered->all());
        $this->assertCount(8, $collection);
    }

    #[Test]
    public function it_gets_the_first_non_null_value(): void
    {
        $collection = new Collection(['nickname' => null, 'name' => 'Ana', 'user' => ['name' => 'Budi']]);

        $this->assertSame('Ana', $collection->getOr('nickname', 'name'));
        $this->assertSame('Ana', $collection->getOr('missing', 'name'));
        $this->assertSame('Budi', $collection->getOr('user.name'));
        $this->assertNull($collection->getOr('nickname'));
    }

    #[Test]
    public function it_wraps_an_array_value_in_a_collection(): void
    {
        $value = (new Collection(['tags' => ['x', 'y']]))->getOr('tags');

        $this->assertInstanceOf(Collection::class, $value);
        $this->assertSame(['x', 'y'], $value->all());
    }

    #[Test]
    public function it_falls_back_to_the_default(): void
    {
        $collection = new Collection(['nickname' => null, 'name' => 'Ana', 'empty' => '']);

        $this->assertSame('default', $collection->getOrWithDefault('default', 'nickname', 'missing'));
        $this->assertSame('default', $collection->getOrWithDefault('default'));
        $this->assertSame('Ana', $collection->getOrWithDefault('default', 'nickname', 'name'));
        $this->assertSame('', $collection->getOrWithDefault('default', 'empty'));
        $this->assertNull($collection->getOrWithDefault(null, 'nickname'));
    }

    #[Test]
    public function it_wraps_an_array_default_in_a_collection(): void
    {
        $value = (new Collection(['nickname' => null]))->getOrWithDefault(['x'], 'nickname');

        $this->assertInstanceOf(Collection::class, $value);
        $this->assertSame(['x'], $value->all());
    }

    #[Test]
    public function it_is_created_by_the_collect_helper(): void
    {
        $collection = collect(['nickname' => null, 'name' => 'Ana']);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame('Ana', $collection->getOr('nickname', 'name'));
        $this->assertSame(['a'], collect('a')->all());
        $this->assertSame([], collect(null)->all());
        $this->assertSame([1], collect(new \Illuminate\Support\Collection([1]))->all());
    }

    #[Test]
    public function it_keeps_its_class_through_laravel_methods(): void
    {
        $this->assertInstanceOf(Collection::class, (new Collection([1, 2]))->map(fn (int $value) => $value * 2));
    }
}
