<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class FunctionsTest extends TestCase
{
    public static function strConcatProvider(): iterable
    {
        yield 'nothing' => [[], ''];
        yield 'one' => [['a'], 'a'];
        yield 'many' => [['a', 'b', 'c'], 'abc'];
        yield 'with empty' => [['a', '', 'b'], 'ab'];
        yield 'with spaces' => [['a ', ' b'], 'a  b'];
    }

    #[Test]
    #[DataProvider('strConcatProvider')]
    public function it_concatenates_strings(array $values, string $expected): void
    {
        $this->assertSame($expected, str_concat(...$values));
    }

    #[Test]
    public function it_is_available_globally(): void
    {
        $this->assertTrue(function_exists('str_concat'));
    }
}
