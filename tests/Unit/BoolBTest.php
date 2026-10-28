<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Error;
use JundFauz\EloquentKompact\Builder\BoolB;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class BoolBTest extends TestCase
{
    public static function conditionProvider(): iterable
    {
        yield 'create true' => [BoolB::create(true), true];
        yield 'create false' => [BoolB::create(false), false];

        yield 'true and true' => [BoolB::create(true)->and(true), true];
        yield 'true and false' => [BoolB::create(true)->and(false), false];
        yield 'false and true' => [BoolB::create(false)->and(true), false];
        yield 'false and false' => [BoolB::create(false)->and(false), false];

        yield 'true or true' => [BoolB::create(true)->or(true), true];
        yield 'true or false' => [BoolB::create(true)->or(false), true];
        yield 'false or true' => [BoolB::create(false)->or(true), true];
        yield 'false or false' => [BoolB::create(false)->or(false), false];

        yield 'chain left to right' => [BoolB::create(true)->or(false)->and(false), false];
        yield 'chain or after and' => [BoolB::create(true)->and(false)->or(true), true];
        yield 'set' => [BoolB::create(false)->set(true), true];
    }

    #[Test]
    #[DataProvider('conditionProvider')]
    public function it_builds_conditions(BoolB $condition, bool $expected): void
    {
        $this->assertSame($expected, $condition->result);
    }

    #[Test]
    public function it_returns_a_new_instance_and_keeps_the_original(): void
    {
        $condition = BoolB::create(true);
        $combined = $condition->and(false);

        $this->assertNotSame($condition, $combined);
        $this->assertTrue($condition->result);
        $this->assertFalse($combined->result);
    }

    #[Test]
    public function it_cannot_modify_the_result(): void
    {
        $condition = BoolB::create(true);

        $this->expectException(Error::class);

        $condition->result = false;
    }

    #[Test]
    public function it_cannot_be_instantiated_directly(): void
    {
        $this->expectException(Error::class);

        new BoolB;
    }
}
