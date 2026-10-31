<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Internal\Database\Driver;
use JundFauz\EloquentKompact\Query\Interval;
use JundFauz\EloquentKompact\Query\Operator;
use JundFauz\EloquentKompact\Query\RawQuery;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class RawQueryTest extends TestCase
{
    public static function expressionProvider(): iterable
    {
        yield 'create empty' => [RawQuery::create(), ''];
        yield 'create from builder' => [RawQuery::create(RawQuery::create('price')->sum()), 'SUM(price)'];

        yield 'zeroIfNull' => [RawQuery::create('price')->zeroIfNull(), 'COALESCE(price, 0)'];
        yield 'replaceIfNull' => [RawQuery::create('email')->replaceIfNull("''"), "NULLIF(email, '')"];
        yield 'isNull' => [RawQuery::create('deleted_at')->isNull(), 'deleted_at IS NULL'];
        yield 'isZeroOrNull single' => [RawQuery::create('a')->isZeroOrNull(), 'a = 0 OR a IS NULL'];
        yield 'isZeroOrNull many' => [RawQuery::create('a')->isZeroOrNull('b'), 'a = 0 OR a IS NULL OR b = 0 OR b IS NULL'];
        yield 'and' => [RawQuery::create('a = 1')->and('b = 2'), 'a = 1 AND b = 2'];
        yield 'and chained' => [RawQuery::create('a = 1')->and('b = 2')->and('c = 3'), 'a = 1 AND b = 2 AND c = 3'];
        yield 'wrap' => [RawQuery::create('a + b')->wrap(), '(a + b)'];
        yield 'sum' => [RawQuery::create('price')->sum(), 'SUM(price)'];
        yield 'max' => [RawQuery::create('price')->max(), 'MAX(price)'];
        yield 'count' => [RawQuery::create('id')->count(), 'COUNT(id)'];

        yield 'plus' => [RawQuery::create('price')->plus('tax', 'shipping'), 'price + tax + shipping'];
        yield 'plus from empty' => [RawQuery::create()->plus('tax', 'shipping'), 'tax + shipping'];
        yield 'minus' => [RawQuery::create('price')->minus('discount'), 'price - discount'];
        yield 'multiply' => [RawQuery::create('price')->multiply('qty'), 'price * qty'];
        yield 'divide' => [RawQuery::create('paid')->divide('total'), 'paid * 1.0 / NULLIF(total, 0)'];
        yield 'divide many' => [RawQuery::create('a')->divide('b', 'c'), 'a * 1.0 / NULLIF(b, 0) / NULLIF(c, 0)'];

        yield 'if' => [
            RawQuery::create('stock > 0')->if("'available'", "'empty'"),
            "CASE WHEN stock > 0 THEN 'available' ELSE 'empty' END",
        ];
        yield 'switchCase' => [
            RawQuery::create('status')->switchCase([1 => "'active'", 2 => "'banned'"], "'unknown'"),
            "\nCASE status\n  WHEN 1 THEN 'active'\n  WHEN 2 THEN 'banned'\n  ELSE 'unknown'\nEND",
        ];
        yield 'switchCase without default' => [
            RawQuery::create('status')->switchCase([1 => "'active'"]),
            "\nCASE status\n  WHEN 1 THEN 'active'\nEND",
        ];
        yield 'switchCase with falsy default' => [
            RawQuery::create('status')->switchCase([1 => '10'], 0),
            "\nCASE status\n  WHEN 1 THEN 10\n  ELSE 0\nEND",
        ];

        yield 'concat' => [RawQuery::create('first_name')->concat("' '", 'last_name'), "CONCAT_WS('', first_name, ' ', last_name)"];
        yield 'cast' => [RawQuery::create('id')->cast(RawQuery::CHAR), 'CAST(id AS CHAR)'];
        yield 'as' => [RawQuery::create('price')->as('total'), 'price AS total'];
        yield 'dateAfter' => [RawQuery::create('created_at')->dateAfter(30), "created_at + INTERVAL '30' DAY"];
        yield 'dateAfter month' => [RawQuery::create('created_at')->dateAfter(2, Interval::Month), "created_at + INTERVAL '2' MONTH"];
        yield 'compare default' => [RawQuery::create('total')->compare(), 'total = ?'];
        yield 'compare operator' => [RawQuery::create('stock')->compare('0', Operator::MoreThanOrSame), 'stock >= 0'];

        yield 'cast builder' => [RawQuery::create('id')->cast(RawQuery::create(RawQuery::CHAR)), 'CAST(id AS CHAR)'];
        yield 'as builder' => [RawQuery::create('price')->as(RawQuery::create('total')), 'price AS total'];
        yield 'select' => [RawQuery::select('id', 'users'), 'SELECT id FROM users'];
        yield 'select many' => [RawQuery::select(['id', 'name'], 'users'), 'SELECT id, name FROM users'];
        yield 'where' => [RawQuery::select('id', 'users')->where('active_is'), 'SELECT id FROM users WHERE active_is = ?'];
        yield 'where operator' => [RawQuery::select('id', 'users')->where('age', Operator::MoreThan, '17'), 'SELECT id FROM users WHERE age > 17'];
        yield 'in' => [RawQuery::create('id')->in('1, 2'), 'id IN (1, 2)'];
        yield 'in subquery' => [RawQuery::create('id')->in(RawQuery::select('user_id', 'posts')), 'id IN (SELECT user_id FROM posts)'];
        yield 'recursive' => [
            RawQuery::recursive('t', 'SELECT 1 AS n', 'SELECT n + 1 FROM t'),
            "WITH RECURSIVE t AS (
SELECT 1 AS n

UNION

SELECT n + 1 FROM t
)
SELECT * FROM t",
        ];
        yield 'recursive union all with columns' => [
            RawQuery::recursive('t', RawQuery::create('SELECT 1 AS n'), 'SELECT n + 1 FROM t WHERE n < 5', true, 'n'),
            "WITH RECURSIVE t AS (
SELECT 1 AS n

UNION ALL

SELECT n + 1 FROM t WHERE n < 5
)
SELECT n FROM t",
        ];

        yield 'nested builder' => [
            RawQuery::create('price')->multiply('qty')->plus(RawQuery::create('shipping')->zeroIfNull()),
            'price * qty + COALESCE(shipping, 0)',
        ];
        yield 'wrap for precedence' => [RawQuery::create('price')->plus('tax')->wrap()->multiply('qty'), '(price + tax) * qty'];
        yield 'builder in replaceIfNull' => [
            RawQuery::create('price')->replaceIfNull(RawQuery::create('discount')->zeroIfNull()),
            'NULLIF(price, COALESCE(discount, 0))',
        ];
        yield 'builder in if' => [
            RawQuery::create('stock')->compare(RawQuery::create('min_stock')->zeroIfNull(), Operator::MoreThan)
                ->if(RawQuery::create('stock')->minus('min_stock'), '0'),
            'CASE WHEN stock > COALESCE(min_stock, 0) THEN stock - min_stock ELSE 0 END',
        ];
        yield 'builder in switchCase values' => [
            RawQuery::create('type')->switchCase([1 => RawQuery::create('price')->multiply('qty')], RawQuery::create('price')),
            "\nCASE type\n  WHEN 1 THEN price * qty\n  ELSE price\nEND",
        ];
        yield 'builder in and' => [
            RawQuery::create('a')->isNull()->and(RawQuery::create('b')->isNull()),
            'a IS NULL AND b IS NULL',
        ];
        yield 'builder in concat' => [
            RawQuery::create('code')->concat(RawQuery::create('id')->cast(RawQuery::CHAR)),
            "CONCAT_WS('', code, CAST(id AS CHAR))",
        ];
        yield 'if with isZeroOrNull subject' => [
            RawQuery::create('initial_weight')
                ->isZeroOrNull('final_weight')
                ->if(RawQuery::NULL, RawQuery::create('initial_weight')->minus('final_weight')),
            'CASE WHEN initial_weight = 0 OR initial_weight IS NULL OR final_weight = 0 OR final_weight IS NULL THEN NULL ELSE initial_weight - final_weight END',
        ];
        yield 'long chain' => [
            RawQuery::create('paid')->sum()->divide(RawQuery::create('total')->sum())->as('paid_ratio'),
            'SUM(paid) * 1.0 / NULLIF(SUM(total), 0) AS paid_ratio',
        ];
    }

    #[Test]
    #[DataProvider('expressionProvider')]
    public function it_builds_expressions(RawQuery $query, string $expected): void
    {
        $this->assertSame($expected, $query->build());
    }

    #[Test]
    public function it_converts_to_a_str(): void
    {
        $str = RawQuery::create('price')->sum()->asStr();

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('SUM(price)', $str->toString());
        $this->assertSame('SUM(price) AS total', $str->asRawQuery()->as('total')->build());
        $this->assertSame('', RawQuery::create()->asStr()->toString());
    }

    #[Test]
    public function it_casts_to_the_built_string(): void
    {
        $query = RawQuery::create('price')->sum();

        $this->assertSame($query->build(), (string) $query);
    }

    #[Test]
    public function it_returns_a_new_instance_and_keeps_the_original(): void
    {
        $query = RawQuery::create('price');
        $sum = $query->sum();

        $this->assertNotSame($query, $sum);
        $this->assertSame('price', $query->build());
        $this->assertSame('SUM(price)', $sum->build());
    }

    #[Test]
    public function it_branches_from_the_same_base(): void
    {
        $price = RawQuery::create('price')->zeroIfNull();

        $this->assertSame('SUM(COALESCE(price, 0))', $price->sum()->build());
        $this->assertSame('MAX(COALESCE(price, 0))', $price->max()->build());
    }

    #[Test]
    public function it_builds_flexible_case(): void
    {
        $this->assertSame("CASE\n  WHEN a = 1 THEN ?\nEND", RawQuery::flexibleCase('a = 1')->build());
        $this->assertSame("CASE\n  WHEN a = 1 THEN ?\n  WHEN b = 2 THEN ?\nEND", RawQuery::flexibleCase(['a = 1', 'b = 2'])->build());
        $this->assertSame(
            "CASE\n  WHEN a = 1 THEN 'one'\n  ELSE 'other'\nEND",
            RawQuery::flexibleCase(['a = 1' => "'one'"], "'other'")->build()
        );
        $this->assertSame("CASE\n  WHEN a = 1 THEN 1\n  ELSE 0\nEND", RawQuery::flexibleCase(['a = 1' => '1'], 0)->build());
        $this->assertSame("CASE\n  WHEN a IS NULL THEN ?\nEND", RawQuery::flexibleCase(RawQuery::create('a')->isNull())->build());
        $this->assertSame(
            "CASE\n  WHEN a IS NULL THEN ?\n  ELSE COALESCE(b, 0)\nEND",
            RawQuery::flexibleCase([RawQuery::create('a')->isNull()], RawQuery::create('b')->zeroIfNull())->build()
        );
    }

    #[Test]
    public function it_chains_from_flexible_case(): void
    {
        $this->assertInstanceOf(RawQuery::class, RawQuery::flexibleCase('a = 1'));
        $this->assertSame("CASE\n  WHEN a = 1 THEN ?\nEND AS b", RawQuery::flexibleCase('a = 1')->as('b')->build());
    }

    #[Test]
    public function it_builds_static_helpers(): void
    {
        $this->assertSame('NOW()', RawQuery::now());
        $this->assertSame('SELECT SUM(price)', RawQuery::queryConcat('SELECT', RawQuery::create('price')->sum()));
    }

    #[Test]
    public function it_builds_relation_paths(): void
    {
        $this->assertSame('users.blog_posts.title', RawQuery::relations('title', 'App\Models\User', 'App\Models\BlogPost')->build());
        $this->assertSame('users', RawQuery::relations('', 'App\Models\User')->build());
        $this->assertInstanceOf(RawQuery::class, RawQuery::relations('title', 'App\Models\User'));
        $this->assertSame('COUNT(users.title)', RawQuery::relations('title', 'App\Models\User')->count()->build());
    }

    #[Test]
    public function it_escapes_strings(): void
    {
        $this->assertSame("''", RawQuery::string());
        $this->assertSame("'available'", RawQuery::string('available'));
        $this->assertSame("'O''Neil'", RawQuery::string("O'Neil"));
    }

    #[Test]
    public function it_builds_diff_for_mysql(): void
    {
        $this->useDriver(Driver::MySQL);

        $this->assertSame(
            'TIMESTAMPDIFF(DAY, NOW(), due_date)',
            RawQuery::create('due_date')->diff(RawQuery::now())->build()
        );
    }

    #[Test]
    public function it_builds_diff_for_mariadb(): void
    {
        $this->useDriver(Driver::MariaDB);

        $this->assertSame(
            'TIMESTAMPDIFF(DAY, NOW(), due_date)',
            RawQuery::create('due_date')->diff(RawQuery::now())->build()
        );
    }

    #[Test]
    public function it_builds_diff_for_postgresql(): void
    {
        $this->useDriver(Driver::PostgresSQL);

        $this->assertSame(
            'EXTRACT(DAY FROM (due_date - NOW()))',
            RawQuery::create('due_date')->diff(RawQuery::now())->build()
        );
    }

    #[Test]
    public function it_rejects_diff_for_unsupported_driver(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Database driver sqlite isn't supported yet.");

        RawQuery::create('due_date')->diff(RawQuery::now());
    }
}
