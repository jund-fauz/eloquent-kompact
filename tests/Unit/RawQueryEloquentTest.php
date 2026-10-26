<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Query\Operator;
use JundFauz\EloquentKompact\Query\RawQuery;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Passes RawQuery instances to Laravel's raw methods without calling build()
 */
class RawQueryEloquentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer');
            $table->integer('price');
            $table->integer('qty');
            $table->integer('shipping')->nullable();
        });

        DB::table('orders')->insert([
            ['customer' => 'a', 'price' => 100, 'qty' => 2, 'shipping' => null],
            ['customer' => 'a', 'price' => 50, 'qty' => 1, 'shipping' => 10],
            ['customer' => 'b', 'price' => 200, 'qty' => 1, 'shipping' => 5],
        ]);
    }

    #[Test]
    public function select_raw_accepts_instance(): void
    {
        $total = DB::table('orders')
            ->selectRaw(RawQuery::create('price')->multiply('qty')->plus(RawQuery::create('shipping')->zeroIfNull())->sum()->as('total'))
            ->value('total');

        $this->assertEquals(465, $total);
    }

    #[Test]
    public function where_raw_accepts_instance_with_bindings(): void
    {
        $count = DB::table('orders')
            ->whereRaw(RawQuery::create('price')->compare(operator: Operator::MoreThanOrSame), [100])
            ->count();

        $this->assertSame(2, $count);
    }

    #[Test]
    public function or_where_raw_accepts_instance(): void
    {
        $ids = DB::table('orders')
            ->whereRaw(RawQuery::create('price')->compare('200'))
            ->orWhereRaw(RawQuery::create('shipping')->isNull())
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1, 3], $ids);
    }

    #[Test]
    public function group_by_raw_and_having_raw_accept_instance(): void
    {
        $customers = DB::table('orders')
            ->select('customer')
            ->groupByRaw(RawQuery::create('customer'))
            ->havingRaw(RawQuery::create('price')->sum()->compare(operator: Operator::MoreThan), [160])
            ->pluck('customer')
            ->all();

        $this->assertSame(['b'], $customers);
    }

    #[Test]
    public function order_by_raw_accepts_instance(): void
    {
        $ids = DB::table('orders')
            ->orderByRaw(RawQuery::create('price')->multiply('qty')->plus(RawQuery::create('shipping')->zeroIfNull()))
            ->pluck('id')
            ->all();

        $this->assertSame([2, 1, 3], $ids);
    }

    #[Test]
    public function db_raw_accepts_instance(): void
    {
        $maxPrice = DB::table('orders')
            ->select(DB::raw(RawQuery::create('price')->max()->as('max_price')))
            ->value('max_price');

        $this->assertEquals(200, $maxPrice);
    }

    #[Test]
    public function table_uses_instance_as_raw_sql(): void
    {
        $query = DB::table(RawQuery::create('orders'));

        $this->assertSame('select * from orders', $query->toSql());
        $this->assertSame(3, $query->count());
    }

    #[Test]
    public function table_does_not_quote_instance(): void
    {
        $query = DB::table(RawQuery::create('orders')->as('o'));

        $this->assertSame('select * from orders AS o', $query->toSql());
        $this->assertSame(3, $query->count());
    }

    #[Test]
    public function table_accepts_instance_as_subquery(): void
    {
        $subquery = RawQuery::queryConcat('(SELECT customer, SUM(price) AS total FROM orders GROUP BY customer)', 'AS t');

        $total = DB::table(RawQuery::create($subquery))->where('customer', 'a')->value('total');

        $this->assertEquals(150, $total);
    }

    #[Test]
    public function table_still_accepts_instance_through_db_raw(): void
    {
        $subquery = RawQuery::queryConcat('(SELECT customer, SUM(price) AS total FROM orders GROUP BY customer)', 'AS t');

        $total = DB::table(DB::raw(RawQuery::create($subquery)))->where('customer', 'a')->value('total');

        $this->assertEquals(150, $total);
    }

    #[Test]
    public function eloquent_builder_accepts_instance(): void
    {
        $model = new class extends Model {
            protected $table = 'orders';
        };

        $result = $model->newQuery()
            ->selectRaw(RawQuery::create('price')->sum()->as('total_price'))
            ->whereRaw(RawQuery::create('customer')->compare(), ['a'])
            ->first();

        $this->assertEquals(150, $result->total_price);
    }
}
