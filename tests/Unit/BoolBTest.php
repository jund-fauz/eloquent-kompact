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
        yield 'initiate true' => [BoolB::initiate(true), true];
        yield 'initiate false' => [BoolB::initiate(false), false];

        yield 'true and true' => [BoolB::initiate(true)->and(true), true];
        yield 'true and false' => [BoolB::initiate(true)->and(false), false];
        yield 'false and true' => [BoolB::initiate(false)->and(true), false];
        yield 'false and false' => [BoolB::initiate(false)->and(false), false];

        yield 'true or true' => [BoolB::initiate(true)->or(true), true];
        yield 'true or false' => [BoolB::initiate(true)->or(false), true];
        yield 'false or true' => [BoolB::initiate(false)->or(true), true];
        yield 'false or false' => [BoolB::initiate(false)->or(false), false];

        yield 'chain left to right' => [BoolB::initiate(true)->or(false)->and(false), false];
        yield 'chain or after and' => [BoolB::initiate(true)->and(false)->or(true), true];
        yield 'set' => [BoolB::initiate(false)->set(true), true];
    }

    #[Test]
    #[DataProvider('conditionProvider')]
    public function it_builds_conditions(BoolB $condition, bool $expected): void
    {
        $this->assertSame($expected, $condition->result);
    }

    #[Test]
    public function it_keeps_create_as_a_deprecated_alias_of_initiate(): void
    {
        $this->assertTrue(BoolB::create(true)->result);
        $this->assertFalse(BoolB::create(false)->result);
        $this->assertFalse(BoolB::create(true)->and(false)->result);
    }

    #[Test]
    public function it_returns_a_new_instance_and_keeps_the_original(): void
    {
        $condition = BoolB::initiate(true);
        $combined = $condition->and(false);

        $this->assertNotSame($condition, $combined);
        $this->assertTrue($condition->result);
        $this->assertFalse($combined->result);
    }

    #[Test]
    public function it_cannot_modify_the_result(): void
    {
        $condition = BoolB::initiate(true);

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
