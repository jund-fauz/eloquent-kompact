<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

use function JundFauz\EloquentKompact\Helper\same_with;

class FunctionsWithNamespaceTest extends TestCase
{
    public static function sameWithProvider(): iterable
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
    #[DataProvider('sameWithProvider')]
    public function it_compares_values_strictly(mixed $first, mixed $second, bool $expected): void
    {
        $this->assertSame($expected, same_with($first, $second));
    }

    #[Test]
    public function it_is_only_available_in_the_package_namespace(): void
    {
        $this->assertTrue(function_exists('JundFauz\EloquentKompact\Helper\same_with'));
        $this->assertFalse(function_exists('same_with'));
    }
}
