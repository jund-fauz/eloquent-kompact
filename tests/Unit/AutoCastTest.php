<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoCast\OrderStatus;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AutoCastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['eloquent-kompact.path.database_enum' => 'JundFauz\EloquentKompact\Tests\Fixtures\AutoCast']);
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status_enum');
            $table->string('kind_enum');
            $table->text('tags_array');
            $table->boolean('active_is');
            $table->decimal('weight_gram', 15, 7);
            $table->decimal('tax_percentage', 5, 2);
            $table->integer('stock_pcs');
            $table->string('note');
            $table->timestamp('paid_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        parent::tearDown();
    }

    #[Test]
    public function it_casts_the_columns_by_their_token(): void
    {
        $casts = (new AutoCastTestOrder)->getCasts();

        $this->assertSame('array', $casts['tags_array']);
        $this->assertSame('boolean', $casts['active_is']);
        $this->assertSame('decimal:7', $casts['weight_gram']);
        $this->assertSame('decimal:2', $casts['tax_percentage']);
        $this->assertArrayNotHasKey('stock_pcs', $casts);
        $this->assertArrayNotHasKey('note', $casts);
    }

    #[Test]
    public function it_casts_an_enum_column_to_the_enum_of_the_table(): void
    {
        $casts = (new AutoCastTestOrder)->getCasts();

        $this->assertSame(OrderStatus::class, $casts['status_enum']);
        $this->assertSame('string', $casts['kind_enum']);
    }

    #[Test]
    public function it_does_not_cast_columns_that_are_not_fillable(): void
    {
        $this->assertArrayNotHasKey('paid_at', (new AutoCastTestOrder)->getCasts());
    }

    #[Test]
    public function it_lets_the_casts_of_the_model_win(): void
    {
        $casts = (new AutoCastTestOrderWithCasts)->getCasts();

        $this->assertSame('integer', $casts['active_is']);
        $this->assertSame('array', $casts['tags_array']);
    }

    #[Test]
    public function it_applies_the_casts_to_the_attributes(): void
    {
        DB::table('orders')->insert([
            'status_enum' => 'paid',
            'kind_enum' => 'x',
            'tags_array' => '["a","b"]',
            'active_is' => 1,
            'weight_gram' => 2.5,
            'tax_percentage' => 11,
            'stock_pcs' => 3,
            'note' => '-',
        ]);

        $order = AutoCastTestOrder::first();

        $this->assertSame(OrderStatus::Paid, $order->status_enum);
        $this->assertSame(['a', 'b'], $order->tags_array);
        $this->assertTrue($order->active_is);
        $this->assertSame('2.5000000', $order->weight_gram);
        $this->assertSame('11.00', $order->tax_percentage);
    }
}

class AutoCastTestOrder extends KompactModel
{
    protected $table = 'orders';

    protected static bool $useSoftDelete = false;
}

class AutoCastTestOrderWithCasts extends KompactModel
{
    protected $table = 'orders';

    protected static bool $useSoftDelete = false;

    protected $casts = ['active_is' => 'integer'];
}
