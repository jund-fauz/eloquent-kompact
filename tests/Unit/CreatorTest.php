<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Creator;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class CreatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Creator::configureDelimiter('_');

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
        $this->assertInstanceOf(Collection::class, Creator::tokens());
        $this->assertTrue(array_is_list(Creator::tokens()->all()));

        $lengths = Creator::tokens()->map(strlen(...))->all();
        $sorted = $lengths;
        rsort($sorted);

        $this->assertSame($sorted, $lengths);
        $this->assertContains('id', Creator::tokens());
        $this->assertNotContains('decimal:7', Creator::tokens());
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
        $plain = Creator::getPlainColumnName($column);

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
        yield 'type has no cast' => [Creator::Type, null];
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

    #[Test]
    public function it_appends_a_custom_token(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => 'kg', 'lb' => ['lb'], 'per_unit' => ['pu', 'decimal:3']]]);

        $this->assertSame('weight_kg', Creator::kg('weight'));
        $this->assertSame('weight_lb', Creator::lb('weight'));
        $this->assertSame('price_pu', Creator::perUnit('price'));
        $this->assertSame('kg', Creator::getCustomToken('kg'));
        $this->assertSame('pu', Creator::getCustomToken('perUnit'));
        $this->assertNull(Creator::getCustomToken('unknown'));
    }

    #[Test]
    public function it_reads_a_custom_token_as_a_column_type(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => 'kg']]);

        $this->assertContains('kg', Creator::tokens());
        $this->assertSame('kg', Creator::getColumnType('weight_kg'));
        $this->assertSame('weight', Creator::getPlainColumnName('weight_kg')->toString());
    }

    #[Test]
    public function it_returns_the_cast_of_a_custom_token(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => 'kg', 'lb' => ['lb'], 'weight' => ['ton', 'decimal:1']]]);

        $this->assertNull(Creator::castOf('kg'));
        $this->assertNull(Creator::castOf('lb'));
        $this->assertSame('decimal:1', Creator::castOf('ton'));
        $this->assertSame('decimal:7', Creator::castOf(Creator::Gram));
    }

    #[Test]
    public function a_custom_cast_wins_over_the_built_in_one(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['gram' => ['gram', 'decimal:3']]]);

        $this->assertSame('decimal:3', Creator::castOf(Creator::Gram));
    }

    #[Test]
    public function it_rebuilds_the_caches_when_the_custom_tokens_change(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => ['kg', 'decimal:3']]]);

        $this->assertSame('decimal:3', Creator::castOf('kg'));
        $this->assertSame('kg', Creator::getColumnType('weight_kg'));

        config(['eloquent-kompact.column.custom_tokens' => ['lb' => ['lb', 'decimal:5']]]);

        $this->assertNull(Creator::castOf('kg'));
        $this->assertSame('decimal:5', Creator::castOf('lb'));
        $this->assertSame('', Creator::getColumnType('weight_kg'));
        $this->assertNotContains('kg', Creator::tokens());
    }

    #[Test]
    public function it_drops_a_removed_custom_token_method(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['kg' => 'kg']]);
        Creator::kg('weight');

        config(['eloquent-kompact.column.custom_tokens' => []]);

        $this->expectException(BadMethodCallException::class);

        Creator::kg('weight');
    }

    #[Test]
    public function it_uses_a_custom_delimiter(): void
    {
        Creator::configureDelimiter('-');

        $this->assertSame('-', Creator::getDelimiter());
        $this->assertSame('user-id', Creator::relation('user'));
        $this->assertSame('id', Creator::getColumnType('user-id'));
        $this->assertSame('-id', Creator::getColumnType('user-id', true));
        $this->assertSame('user', Creator::getPlainColumnName('user-id')->toString());
        $this->assertSame('', Creator::getColumnType('user_id'));

        // Only the token gets the delimiter; the prefix stays snake case
        $this->assertSame('author_user-id', \JundFauz\EloquentKompact\Column\Convert::modelToColumn('App\Models\User', 'author'));
    }

    #[Test]
    public function it_quotes_the_delimiter_in_the_matcher(): void
    {
        Creator::configureDelimiter('.');

        $this->assertSame('id', Creator::getColumnType('user.id'));
        $this->assertSame('', Creator::getColumnType('user_id'));
    }
}
