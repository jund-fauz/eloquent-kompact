<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use Illuminate\Support\Fluent;
use JundFauz\EloquentKompact\Column\Checker;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class CheckerTest extends TestCase
{
    public static function columnProvider(): iterable
    {
        yield 'relation' => ['isRelation', 'author_user_id', true];
        yield 'enum' => ['isEnum', 'status_enum', true];
        yield 'timestamp' => ['isTimestamp', 'paid_at', true];
        yield 'time' => ['isTime', 'start_time', true];
        yield 'type' => ['isType', 'commentable_type', true];
        yield 'other token' => ['isRelation', 'status_enum', false];
        yield 'token in the middle' => ['isRelation', 'user_identity', false];
        yield 'token before another token' => ['isTimestamp', 'created_at_backup', false];
        yield 'only the last token' => ['isEnum', 'user_id_enum', true];
        yield 'not the last token' => ['isRelation', 'user_id_enum', false];
        yield 'plain' => ['isRelation', 'name', false];
        yield 'token without the delimiter' => ['isRelation', 'paid', false];
    }

    #[Test]
    #[DataProvider('columnProvider')]
    public function it_checks_the_token_at_the_end_of_a_column(string $method, string $column, bool $expected): void
    {
        $this->assertSame($expected, Checker::{$method}($column));
    }

    #[Test]
    public function it_checks_a_column_definition(): void
    {
        $this->assertTrue(Checker::isEnum(new Fluent(['name' => 'status_enum'])));
        $this->assertFalse(Checker::isEnum(new Fluent(['name' => 'status'])));
    }

    #[Test]
    public function it_checks_a_custom_token(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => 'kg', 'per_unit' => ['pu', 'decimal:3']]]);

        $this->assertTrue(Checker::isKg('weight_kg'));
        $this->assertFalse(Checker::isKg('weight_lb'));
        $this->assertTrue(Checker::isPerUnit('price_pu'));
    }

    #[Test]
    public function it_throws_on_unknown_methods(): void
    {
        $this->expectException(BadMethodCallException::class);

        Checker::isUnknown('status');
    }
}
