<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Error;
use JundFauz\EloquentKompact\Builder\BoolB;
use JundFauz\EloquentKompact\Internal\Logic\Compare;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class BoolBTest extends TestCase
{
    public static function conditionProvider(): iterable
    {
        yield 'initiate true' => [BoolB::initiate(true), true];
        yield 'initiate false' => [BoolB::initiate(false), false];

        yield 'true and same' => [BoolB::initiate(true)->and('a')->sameWith('a'), true];
        yield 'true and not same' => [BoolB::initiate(true)->and('a')->sameWith('b'), false];
        yield 'false and same' => [BoolB::initiate(false)->and('a')->sameWith('a'), false];
        yield 'false and not same' => [BoolB::initiate(false)->and('a')->sameWith('b'), false];

        yield 'true or same' => [BoolB::initiate(true)->or('a')->sameWith('a'), true];
        yield 'true or not same' => [BoolB::initiate(true)->or('a')->sameWith('b'), true];
        yield 'false or same' => [BoolB::initiate(false)->or('a')->sameWith('a'), true];
        yield 'false or not same' => [BoolB::initiate(false)->or('a')->sameWith('b'), false];

        yield 'true and notSameWith different' => [BoolB::initiate(true)->and('a')->notSameWith('b'), true];
        yield 'true and notSameWith same' => [BoolB::initiate(true)->and('a')->notSameWith('a'), false];
        yield 'false or notSameWith different' => [BoolB::initiate(false)->or('a')->notSameWith('b'), true];
        yield 'false or notSameWith same' => [BoolB::initiate(false)->or('a')->notSameWith('a'), false];

        yield 'strict comparison' => [BoolB::initiate(true)->and(1)->sameWith('1'), false];
        yield 'null is compared' => [BoolB::initiate(true)->and(null)->sameWith(null), true];

        yield 'chain left to right' => [BoolB::initiate(true)->or(1)->sameWith(2)->and(1)->sameWith(2), false];
        yield 'chain or after and' => [BoolB::initiate(true)->and(1)->sameWith(2)->or(1)->sameWith(1), true];

        yield 'compare same' => [BoolB::compare('a')->sameWith('a'), true];
        yield 'compare not same' => [BoolB::compare('a')->sameWith('b'), false];
        yield 'compare notSameWith different' => [BoolB::compare('a')->notSameWith('b'), true];
        yield 'compare notSameWith same' => [BoolB::compare('a')->notSameWith('a'), false];
        yield 'compare strict' => [BoolB::compare(1)->sameWith('1'), false];
        yield 'compare null' => [BoolB::compare(null)->sameWith(null), true];
        yield 'compare then and' => [BoolB::compare(1)->sameWith(1)->and(2)->sameWith(3), false];
        yield 'compare then or' => [BoolB::compare(1)->sameWith(2)->or(3)->sameWith(3), true];
        yield 'compare chain left to right' => [BoolB::compare(1)->sameWith(1)->or(2)->sameWith(3)->and(4)->sameWith(5), false];
    }

    #[Test]
    #[DataProvider('conditionProvider')]
    public function it_builds_conditions(BoolB $condition, bool $expected): void
    {
        $this->assertSame($expected, $condition->result);
    }

    #[Test]
    public function it_returns_a_compare_from_and_or(): void
    {
        $this->assertInstanceOf(Compare::class, BoolB::initiate(true)->and('a'));
        $this->assertInstanceOf(Compare::class, BoolB::initiate(true)->or('a'));
    }

    #[Test]
    public function it_returns_a_compare_from_compare(): void
    {
        $this->assertInstanceOf(Compare::class, BoolB::compare('a'));
    }

    #[Test]
    public function it_no_longer_has_create(): void
    {
        $this->assertFalse(method_exists(BoolB::class, 'create'));
    }

    #[Test]
    public function it_returns_a_new_instance_and_keeps_the_original(): void
    {
        $condition = BoolB::initiate(true);
        $combined = $condition->and('a')->sameWith('b');

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
    public function it_cannot_call_set_from_outside(): void
    {
        $this->expectException(Error::class);

        BoolB::initiate(false)->set(true);
    }

    #[Test]
    public function it_cannot_be_instantiated_directly(): void
    {
        $this->expectException(Error::class);

        new BoolB;
    }

    #[Test]
    public function it_cannot_instantiate_compare_directly(): void
    {
        $this->expectException(Error::class);

        new Compare('a', fn (bool $condition) => BoolB::initiate($condition));
    }
}
