<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use InvalidArgumentException;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ConvertTest extends TestCase
{
    private const string Model = 'App\Models\WorkOrderLedger';

    #[Test]
    public function it_converts_a_model_to_a_relation(): void
    {
        $this->assertSame('workOrderLedger', Convert::modelToRelation(self::Model));
        $this->assertSame('workOrderLedgers', Convert::modelToRelation(self::Model, true));
        $this->assertSame('work_order_ledger', Convert::modelToRelation(self::Model, false, true));
        $this->assertSame('work_order_ledgers', Convert::modelToRelation(self::Model, true, true));
    }

    #[Test]
    public function it_converts_a_model_to_a_relation_with_a_prefix(): void
    {
        $model = ['latest' => self::Model];

        $this->assertSame('latestWorkOrderLedger', Convert::modelToRelation($model));
        $this->assertSame('latestWorkOrderLedgers', Convert::modelToRelation($model, true));
        $this->assertSame('latest_work_order_ledger', Convert::modelToRelation($model, false, true));
        $this->assertSame('latest_work_order_ledgers', Convert::modelToRelation($model, true, true));
    }

    #[Test]
    public function it_accepts_a_prefix_of_several_words(): void
    {
        $this->assertSame('latestActiveWorkOrderLedger', Convert::modelToRelation(['latest_active' => self::Model]));
        $this->assertSame('latest_active_work_order_ledger', Convert::modelToRelation(['latestActive' => self::Model], false, true));
    }

    public static function invalidPrefixes(): array
    {
        return [
            'several keys' => [['latest' => self::Model, 'oldest' => self::Model]],
            'a list of one' => [[self::Model]],
            'a list of two' => [[self::Model, self::Model]],
            'empty' => [[]],
        ];
    }

    #[Test]
    #[DataProvider('invalidPrefixes')]
    public function it_throws_on_another_array(array $model): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A prefixed model has to be [prefix => model class].');

        Convert::modelToRelation($model);
    }

    #[Test]
    public function it_converts_a_model_to_a_column(): void
    {
        $this->assertSame('work_order_ledger_id', Convert::modelToColumn(self::Model));
        $this->assertSame('user_id', Convert::modelToColumn('User'));
        $this->assertSame('author_work_order_ledger_id', Convert::modelToColumn(self::Model, 'author'));
        $this->assertSame('work_order_ledger_id', Convert::modelToColumn(self::Model, ''));
    }

    #[Test]
    public function it_converts_a_boolean_column_to_strings(): void
    {
        $this->assertSame(['Not active', 'Active'], Convert::booleanToString('active_is'));
        $this->assertSame(['Not verified', 'Verified'], Convert::booleanToString('verified'));
    }

    #[Test]
    public function it_translates_the_boolean_strings(): void
    {
        $this->app->setLocale('id');

        $this->assertSame(['Tidak active', 'Active'], Convert::booleanToString('active_is'));
    }

    #[Test]
    public function it_converts_hours_and_days_to_seconds(): void
    {
        $this->assertSame(0, Convert::hourToSecond(0));
        $this->assertSame(3600, Convert::hourToSecond(1));
        $this->assertSame(7200, Convert::hourToSecond(2));
        $this->assertSame(-3600, Convert::hourToSecond(-1));

        $this->assertSame(0, Convert::dayToSecond(0));
        $this->assertSame(86400, Convert::dayToSecond(1));
        $this->assertSame(604800, Convert::dayToSecond(7));
    }
}
