<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
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
        $collection = new Collection(['nickname' => null, 'name' => 'Ana', 'age' => 20, 'active' => false, 'user' => ['name' => 'Budi']]);

        $this->assertStr('Ana', $collection->getOr('nickname', 'name'));
        $this->assertStr('Ana', $collection->getOr('missing', 'name'));
        $this->assertStr('Budi', $collection->getOr('user.name'));
        $this->assertSame(20, $collection->getOr('nickname', 'age'));
        $this->assertFalse($collection->getOr('active'));
        $this->assertNull($collection->getOr('nickname'));
    }

    #[Test]
    public function it_wraps_a_string_value_in_a_str(): void
    {
        $collection = new Collection(['name' => 'Ana', 'empty' => '']);

        $this->assertSame('ANA', $collection->getOr('name')->upper()->toString());
        $this->assertStr('', $collection->getOr('empty'));
    }

    #[Test]
    public function it_wraps_an_array_value_in_a_collection(): void
    {
        $value = (new Collection(['tags' => ['x', 'y']]))->getOr('tags');

        $this->assertInstanceOf(Collection::class, $value);
        $this->assertSame(['x', 'y'], $value->all());
    }

    #[Test]
    public function it_wraps_the_value_from_get(): void
    {
        $collection = new Collection(['name' => 'Ana', 'age' => 20, 'tags' => ['x', 'y']]);

        $this->assertStr('Ana', $collection->get('name'));
        $this->assertSame(20, $collection->get('age'));
        $this->assertInstanceOf(Collection::class, $collection->get('tags'));
        $this->assertSame(['x', 'y'], $collection->get('tags')->all());
        $this->assertStr('Guest', $collection->get('missing', 'Guest'));
        $this->assertNull($collection->get('missing'));
    }

    #[Test]
    public function it_gets_the_items_around_a_value(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 'x', 'c' => ['y']]);

        $this->assertStr('x', $collection->after(1));
        $this->assertSame(['y'], $collection->after('x')->all());
        $this->assertSame(1, $collection->before('x'));
        $this->assertNull($collection->before(1));
        $this->assertNull($collection->after(fn (mixed $value) => is_array($value)));
        $this->assertSame(2, (new Collection([1, 2, 3]))->after(1));
    }

    #[Test]
    public function it_falls_back_to_the_default(): void
    {
        $collection = new Collection(['nickname' => null, 'name' => 'Ana', 'empty' => '']);

        $this->assertStr('default', $collection->getOrWithDefault('default', 'nickname', 'missing'));
        $this->assertStr('default', $collection->getOrWithDefault('default'));
        $this->assertStr('Ana', $collection->getOrWithDefault('default', 'nickname', 'name'));
        $this->assertStr('', $collection->getOrWithDefault('default', 'empty'));
        $this->assertSame(0, $collection->getOrWithDefault(0, 'nickname'));
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
    public function it_gets_the_value_or_the_default_as_a_collection(): void
    {
        $collection = new Collection(['nickname' => null, 'name' => 'Ana', 'age' => 20, 'tags' => ['x', 'y']]);

        $this->assertInstanceOf(Collection::class, $collection->getOrWithDefaultAsCollection('Guest', 'name'));
        $this->assertSame(['Ana'], $collection->getOrWithDefaultAsCollection('Guest', 'nickname', 'name')->all());
        $this->assertSame(['Guest'], $collection->getOrWithDefaultAsCollection('Guest', 'nickname')->all());
        $this->assertSame(['x', 'y'], $collection->getOrWithDefaultAsCollection([], 'tags')->all());
        $this->assertSame(['z'], $collection->getOrWithDefaultAsCollection(['z'], 'nickname')->all());
        $this->assertSame([20], $collection->getOrWithDefaultAsCollection(0, 'age')->all());
        $this->assertSame([], $collection->getOrWithDefaultAsCollection(null, 'nickname')->all());
    }

    #[Test]
    public function it_is_created_by_the_collect_helper(): void
    {
        $collection = collect(['nickname' => null, 'name' => 'Ana']);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertStr('Ana', $collection->getOr('nickname', 'name'));
        $this->assertSame(['a'], collect('a')->all());
        $this->assertSame([], collect(null)->all());
        $this->assertSame([1], collect(new \Illuminate\Support\Collection([1]))->all());
    }

    #[Test]
    public function it_keeps_its_class_through_laravel_methods(): void
    {
        $this->assertInstanceOf(Collection::class, (new Collection([1, 2]))->map(fn (int $value) => $value * 2));
    }

    private function assertStr(string $expected, mixed $actual): void
    {
        $this->assertInstanceOf(Str::class, $actual);
        $this->assertSame($expected, $actual->toString());
    }
}
