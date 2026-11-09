<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class CreatorTest extends TestCase
{
    protected function tearDown(): void
    {
        ColumnCreator::configureDelimiter('_');

        parent::tearDown();
    }

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
        yield 'type' => ['type', 'status_type'];
    }

    #[Test]
    #[DataProvider('tokenMethodProvider')]
    public function it_appends_the_token(string $method, string $expected): void
    {
        $this->assertSame($expected, ColumnCreator::{$method}('status'));
    }

    #[Test]
    public function it_creates_quantity_columns(): void
    {
        $this->assertSame(['quantity_gram', 'quantity_pcs'], ColumnCreator::quantity());
    }

    #[Test]
    public function it_throws_on_unknown_token_methods(): void
    {
        $this->expectException(BadMethodCallException::class);

        ColumnCreator::unknown('status');
    }

    #[Test]
    public function it_throws_on_constants_that_are_not_tokens(): void
    {
        $this->expectException(BadMethodCallException::class);

        ColumnCreator::casts('status');
    }

    #[Test]
    public function it_lists_the_longest_tokens_first(): void
    {
        $this->assertInstanceOf(Collection::class, ColumnCreator::tokens());
        $this->assertTrue(array_is_list(ColumnCreator::tokens()->all()));

        $lengths = ColumnCreator::tokens()->map(strlen(...))->all();
        $sorted = $lengths;
        rsort($sorted);

        $this->assertSame($sorted, $lengths);
        $this->assertContains('id', ColumnCreator::tokens());
        $this->assertNotContains('decimal:7', ColumnCreator::tokens());
    }

    public static function plainColumnProvider(): iterable
    {
        yield 'relation' => ['author_user_id', 'author_user'];
        yield 'enum' => ['status_enum', 'status'];
        yield 'timestamp' => ['created_at', 'created'];
        yield 'time' => ['start_time', 'start'];
        yield 'type' => ['commentable_type', 'commentable'];
        yield 'plain' => ['name', 'name'];
        yield 'token not at the end' => ['id_card', 'id_card'];
        yield 'only the last token' => ['user_id_enum', 'user_id'];
    }

    #[Test]
    #[DataProvider('plainColumnProvider')]
    public function it_removes_the_token(string $column, string $expected): void
    {
        $plain = ColumnCreator::getPlainColumnName($column);

        $this->assertInstanceOf(Str::class, $plain);
        $this->assertSame($expected, $plain->toString());
    }

    public static function columnTypeProvider(): iterable
    {
        yield 'relation' => ['author_user_id', false, 'id'];
        yield 'relation with delimiter' => ['author_user_id', true, '_id'];
        yield 'time not timestamp' => ['start_time', false, 'time'];
        yield 'percentage' => ['tax_percentage', false, 'percentage'];
        yield 'type' => ['commentable_type', false, 'type'];
        yield 'type with delimiter' => ['commentable_type', true, '_type'];
        yield 'plain' => ['name', false, ''];
        yield 'plain with delimiter' => ['name', true, ''];
        yield 'token not at the end' => ['user_identity', false, ''];
        yield 'only the last token' => ['user_id_enum', false, 'enum'];
        yield 'token without the delimiter' => ['paid', false, ''];
        yield 'longest token without the delimiter' => ['xpercentage', false, ''];
    }

    #[Test]
    #[DataProvider('columnTypeProvider')]
    public function it_reads_the_column_type(string $column, bool $clean, string $expected): void
    {
        $this->assertSame($expected, ColumnCreator::getColumnType($column, $clean));
    }

    public static function castProvider(): iterable
    {
        yield 'array' => [ColumnCreator::Array, 'array'];
        yield 'boolean' => [ColumnCreator::Boolean, 'boolean'];
        yield 'gram' => [ColumnCreator::Gram, 'decimal:7'];
        yield 'percentage' => [ColumnCreator::Percentage, 'decimal:2'];
        yield 'enum has no cast' => [ColumnCreator::Enum, null];
        yield 'relation has no cast' => [ColumnCreator::Relation, null];
        yield 'type has no cast' => [ColumnCreator::Type, null];
        yield 'unknown' => ['_unknown', null];
    }

    #[Test]
    #[DataProvider('castProvider')]
    public function it_returns_the_cast_of_a_token(string $token, ?string $expected): void
    {
        $this->assertSame($expected, ColumnCreator::castOf($token));
    }

    #[Test]
    public function it_always_uses_the_suffix_scheme(): void
    {
        $this->assertSame('user_id', ColumnCreator::relation('user'));
        $this->assertSame('user', ColumnCreator::getPlainColumnName('user_id')->toString());
        $this->assertSame('', ColumnCreator::getColumnType('id_user'));
    }

    #[Test]
    public function it_appends_a_custom_token(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => 'kg', 'lb' => ['lb'], 'per_unit' => ['pu', 'decimal:3']]]);

        $this->assertSame('weight_kg', ColumnCreator::kg('weight'));
        $this->assertSame('weight_lb', ColumnCreator::lb('weight'));
        $this->assertSame('price_pu', ColumnCreator::perUnit('price'));
        $this->assertSame('kg', ColumnCreator::getCustomToken('kg'));
        $this->assertSame('pu', ColumnCreator::getCustomToken('perUnit'));
        $this->assertNull(ColumnCreator::getCustomToken('unknown'));
    }

    #[Test]
    public function it_reads_a_custom_token_as_a_column_type(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => 'kg']]);

        $this->assertContains('kg', ColumnCreator::tokens());
        $this->assertSame('kg', ColumnCreator::getColumnType('weight_kg'));
        $this->assertSame('weight', ColumnCreator::getPlainColumnName('weight_kg')->toString());
    }

    #[Test]
    public function it_returns_the_cast_of_a_custom_token(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => 'kg', 'lb' => ['lb'], 'weight' => ['ton', 'decimal:1']]]);

        $this->assertNull(ColumnCreator::castOf('kg'));
        $this->assertNull(ColumnCreator::castOf('lb'));
        $this->assertSame('decimal:1', ColumnCreator::castOf('ton'));
        $this->assertSame('decimal:7', ColumnCreator::castOf(ColumnCreator::Gram));
    }

    #[Test]
    public function a_custom_cast_wins_over_the_built_in_one(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['gram' => ['gram', 'decimal:3']]]);

        $this->assertSame('decimal:3', ColumnCreator::castOf(ColumnCreator::Gram));
    }

    #[Test]
    public function it_rebuilds_the_caches_when_the_custom_tokens_change(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => ['kg', 'decimal:3']]]);

        $this->assertSame('decimal:3', ColumnCreator::castOf('kg'));
        $this->assertSame('kg', ColumnCreator::getColumnType('weight_kg'));

        config(['eloquent-kompact.column.custom_tokens' => ['lb' => ['lb', 'decimal:5']]]);

        $this->assertNull(ColumnCreator::castOf('kg'));
        $this->assertSame('decimal:5', ColumnCreator::castOf('lb'));
        $this->assertSame('', ColumnCreator::getColumnType('weight_kg'));
        $this->assertNotContains('kg', ColumnCreator::tokens());
    }

    #[Test]
    public function it_drops_a_removed_custom_token_method(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => 'kg']]);
        ColumnCreator::kg('weight');

        config(['eloquent-kompact.column.custom_tokens' => []]);

        $this->expectException(BadMethodCallException::class);

        ColumnCreator::kg('weight');
    }

    #[Test]
    public function it_uses_a_custom_delimiter(): void
    {
        ColumnCreator::configureDelimiter('-');

        $this->assertSame('-', ColumnCreator::getDelimiter());
        $this->assertSame('user-id', ColumnCreator::relation('user'));
        $this->assertSame('id', ColumnCreator::getColumnType('user-id'));
        $this->assertSame('-id', ColumnCreator::getColumnType('user-id', true));
        $this->assertSame('user', ColumnCreator::getPlainColumnName('user-id')->toString());
        $this->assertSame('', ColumnCreator::getColumnType('user_id'));

        // Only the token gets the delimiter; the prefix stays snake case
        $this->assertSame('author_user-id', \JundFauz\EloquentKompact\Column\Convert::modelToColumn('App\Models\User', 'author'));
    }

    #[Test]
    public function it_quotes_the_delimiter_in_the_matcher(): void
    {
        ColumnCreator::configureDelimiter('.');

        $this->assertSame('id', ColumnCreator::getColumnType('user.id'));
        $this->assertSame('', ColumnCreator::getColumnType('user_id'));
    }
}
