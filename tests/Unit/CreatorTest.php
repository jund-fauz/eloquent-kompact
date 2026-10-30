<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Creator;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class CreatorTest extends TestCase
{
    public static function tokenMethodProvider(): iterable
    {
        yield 'enum' => ['enum', 'status_enum'];
        yield 'array' => ['array', 'status_array'];
        yield 'gram' => ['gram', 'status_gram'];
        yield 'pcs' => ['pcs', 'status_pcs'];
        yield 'percentage' => ['percentage', 'status_percentage'];
        yield 'timestamp' => ['timestamp', 'status_at'];
        yield 'time' => ['time', 'status_time'];
        yield 'boolean' => ['boolean', 'status_is'];
        yield 'dropdown' => ['dropdown', 'status_dropdown'];
        yield 'relation' => ['relation', 'status_id'];
    }

    #[Test]
    #[DataProvider('tokenMethodProvider')]
    public function it_appends_the_token(string $method, string $expected): void
    {
        $this->assertSame($expected, Creator::{$method}('status'));
    }

    #[Test]
    public function it_creates_quantity_columns(): void
    {
        $this->assertSame(['quantity_gram', 'quantity_pcs'], Creator::quantity());
    }

    #[Test]
    public function it_throws_on_unknown_token_methods(): void
    {
        $this->expectException(BadMethodCallException::class);

        Creator::unknown('status');
    }

    #[Test]
    public function it_throws_on_constants_that_are_not_tokens(): void
    {
        $this->expectException(BadMethodCallException::class);

        Creator::casts('status');
    }

    #[Test]
    public function it_lists_the_longest_tokens_first(): void
    {
        $lengths = array_map(strlen(...), Creator::tokens());
        $sorted = $lengths;
        rsort($sorted);

        $this->assertSame($sorted, $lengths);
        $this->assertContains('_id', Creator::tokens());
        $this->assertNotContains('decimal:7', Creator::tokens());
    }

    public static function plainColumnProvider(): iterable
    {
        yield 'relation' => ['author_user_id', 'author_user'];
        yield 'enum' => ['status_enum', 'status'];
        yield 'timestamp' => ['created_at', 'created'];
        yield 'time' => ['start_time', 'start'];
        yield 'plain' => ['name', 'name'];
        yield 'token not at the end' => ['id_card', 'id_card'];
        yield 'only the last token' => ['user_id_enum', 'user_id'];
    }

    #[Test]
    #[DataProvider('plainColumnProvider')]
    public function it_removes_the_token(string $column, string $expected): void
    {
        $plain = Creator::getPlainColumnName($column);

        $this->assertInstanceOf(Str::class, $plain);
        $this->assertSame($expected, $plain->toString());
    }

    public static function columnTypeProvider(): iterable
    {
        yield 'relation' => ['author_user_id', false, '_id'];
        yield 'relation clean' => ['author_user_id', true, 'id'];
        yield 'time not timestamp' => ['start_time', false, '_time'];
        yield 'percentage' => ['tax_percentage', true, 'percentage'];
        yield 'plain' => ['name', false, ''];
        yield 'plain clean' => ['name', true, ''];
    }

    #[Test]
    #[DataProvider('columnTypeProvider')]
    public function it_reads_the_column_type(string $column, bool $clean, string $expected): void
    {
        $this->assertSame($expected, Creator::getColumnType($column, $clean));
    }

    public static function castProvider(): iterable
    {
        yield 'array' => [Creator::Array, 'array'];
        yield 'boolean' => [Creator::Boolean, 'boolean'];
        yield 'gram' => [Creator::Gram, 'decimal:7'];
        yield 'percentage' => [Creator::Percentage, 'decimal:2'];
        yield 'enum has no cast' => [Creator::Enum, null];
        yield 'relation has no cast' => [Creator::Relation, null];
        yield 'unknown' => ['_unknown', null];
    }

    #[Test]
    #[DataProvider('castProvider')]
    public function it_returns_the_cast_of_a_token(string $token, ?string $expected): void
    {
        $this->assertSame($expected, Creator::castOf($token));
    }

    #[Test]
    public function it_always_uses_the_suffix_scheme(): void
    {
        $this->assertSame('user_id', Creator::relation('user'));
        $this->assertSame('user', Creator::getPlainColumnName('user_id')->toString());
        $this->assertSame('', Creator::getColumnType('id_user'));
    }
}
