<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use JundFauz\EloquentKompact\Helper\Arr;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ArrTest extends TestCase
{
    #[Test]
    public function it_pulls_the_value_of_one_key(): void
    {
        $data = ['name' => 'Ana', 'email' => 'ana@example.com'];

        $this->assertSame('Ana', Arr::pullMany($data, 'name'));
        $this->assertSame(['email' => 'ana@example.com'], $data);
    }

    #[Test]
    public function it_pulls_several_keys_as_key_value_pairs(): void
    {
        $data = ['name' => 'Ana', 'email' => 'ana@example.com', 'age' => 20];

        $this->assertSame(['email' => 'ana@example.com', 'name' => 'Ana'], Arr::pullMany($data, 'email', 'name'));
        $this->assertSame(['age' => 20], $data);
    }

    #[Test]
    public function it_pulls_a_missing_key_as_null(): void
    {
        $data = ['name' => 'Ana'];

        $this->assertNull(Arr::pullMany($data, 'missing'));
        $this->assertSame(['name' => 'Ana', 'missing' => null], Arr::pullMany($data, 'name', 'missing'));
        $this->assertSame([], $data);
    }

    #[Test]
    public function it_pulls_with_dot_notation(): void
    {
        $data = ['user' => ['name' => 'Ana', 'age' => 20]];

        $this->assertSame('Ana', Arr::pullMany($data, 'user.name'));
        $this->assertSame(['user' => ['age' => 20]], $data);
    }

    #[Test]
    public function it_checks_that_no_key_exists(): void
    {
        $array = ['name' => 'Ana', 'nickname' => null];

        $this->assertTrue(Arr::isKeysDontExist($array, 'email'));
        $this->assertTrue(Arr::isKeysDontExist($array, 'email', 'age'));
        $this->assertFalse(Arr::isKeysDontExist($array, 'name'));
        $this->assertFalse(Arr::isKeysDontExist($array, 'email', 'name'));
        $this->assertFalse(Arr::isKeysDontExist($array, 'nickname'));
        $this->assertTrue(Arr::isKeysDontExist($array));
        $this->assertTrue(Arr::isKeysDontExist([], 'name'));
    }
}
