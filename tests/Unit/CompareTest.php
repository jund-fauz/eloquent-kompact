<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Error;
use JundFauz\EloquentKompact\Helper\Compare;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

class CompareTest extends TestCase
{
    public static function valueProvider(): iterable
    {
        $object = new stdClass;

        yield 'same string' => ['a', 'a', true];
        yield 'different string' => ['a', 'b', false];
        yield 'same int' => [1, 1, true];
        yield 'int and numeric string' => [1, '1', false];
        yield 'int and float' => [1, 1.0, false];
        yield 'null and empty string' => [null, '', false];
        yield 'null and zero' => [null, 0, false];
        yield 'null and false' => [null, false, false];
        yield 'both null' => [null, null, true];
        yield 'same array' => [['a' => 1], ['a' => 1], true];
        yield 'array with different order' => [['a' => 1, 'b' => 2], ['b' => 2, 'a' => 1], false];
        yield 'same object' => [$object, $object, true];
        yield 'equal objects' => [new stdClass, new stdClass, false];
    }

    #[Test]
    #[DataProvider('valueProvider')]
    public function it_checks_values_are_the_same(mixed $value, mixed $otherValue, bool $expected): void
    {
        $this->assertSame($expected, Compare::of($value)->sameWith($otherValue));
    }

    #[Test]
    #[DataProvider('valueProvider')]
    public function it_checks_values_are_not_the_same(mixed $value, mixed $otherValue, bool $expected): void
    {
        $this->assertSame(! $expected, Compare::of($value)->notSameWith($otherValue));
    }

    #[Test]
    public function it_compares_the_same_value_several_times(): void
    {
        $compare = Compare::of('a');

        $this->assertTrue($compare->sameWith('a'));
        $this->assertFalse($compare->sameWith('b'));
        $this->assertTrue($compare->notSameWith('b'));
    }

    #[Test]
    public function it_cannot_be_instantiated_directly(): void
    {
        $this->expectException(Error::class);

        new Compare;
    }
}
