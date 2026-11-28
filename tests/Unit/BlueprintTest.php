<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Schema\Blueprint;
use JundFauz\EloquentKompact\Tests\Fixtures\Blueprint\PlainRecord;
use JundFauz\EloquentKompact\Tests\Fixtures\Blueprint\Stock;
use JundFauz\EloquentKompact\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use const JundFauz\EloquentKompact\Column\ALL_NULLABLE;

enum BlueprintTestStatusEnum: string
{
    case Draft = 'draft';
    case Zero = '0';
}

enum BlueprintTestPriorityEnum: int
{
    case Zero = 0;
    case High = 2;
}

class BlueprintTestCustomSubclass extends Blueprint
{
    public function pcs(string $column, bool $big = true): ColumnDefinition
    {
        return parent::pcs($column, $big);
    }
}

class BlueprintTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['eloquent-kompact.path.model' => 'JundFauz\\EloquentKompact\\Tests\\Fixtures\\Blueprint']);
    }

    protected function tearDown(): void
    {
        Blueprint::flushMacros();

        parent::tearDown();
    }

    private function blueprint(string $table = 'items'): Blueprint
    {
        $connection = DB::connection();
        $connection->getSchemaBuilder();

        return new Blueprint($connection, $table);
    }

    #[Test]
    public function it_resolves_the_kompact_blueprint_in_schema_create_and_table(): void
    {
        $resolvedInCreate = null;
        $resolvedInTable = null;

        Schema::create('blueprint_probe', function (BaseBlueprint $table) use (&$resolvedInCreate) {
            $resolvedInCreate = $table;
            $table->id();
            $table->gram('net_weight');
        });

        Schema::table('blueprint_probe', function (BaseBlueprint $table) use (&$resolvedInTable) {
            $resolvedInTable = $table;
            $table->pcs('box')->default(0);
        });

        $this->assertInstanceOf(Blueprint::class, $resolvedInCreate);
        $this->assertInstanceOf(Blueprint::class, $resolvedInTable);
        $this->assertSame(['id', 'net_weight_gram', 'box_pcs'], Schema::getColumnListing('blueprint_probe'));
    }

    #[Test]
    public function it_can_be_subclassed_by_downstream_applications(): void
    {
        $this->app->bind(BaseBlueprint::class, BlueprintTestCustomSubclass::class);

        $capturedPcs = null;
        Schema::create('custom_blueprint_items', function (BaseBlueprint $table) use (&$capturedPcs) {
            $this->assertInstanceOf(BlueprintTestCustomSubclass::class, $table);
            $table->id();
            $capturedPcs = $table->pcs('bundle');
        });

        $this->assertSame('bigInteger', $capturedPcs->type);
        $this->assertTrue($capturedPcs->unsigned);
    }

    #[Test]
    public function it_creates_string_casts_enum_columns_with_backed_enum_scalar_and_zero_defaults(): void
    {
        $table = $this->blueprint();

        $noDefault = $table->stringCastsEnum('status');
        $stringEnumDefault = $table->stringCastsEnum('phase', BlueprintTestStatusEnum::Draft);
        $zeroEnumDefault = $table->stringCastsEnum('code', BlueprintTestStatusEnum::Zero);
        $intEnumDefault = $table->stringCastsEnum('priority', BlueprintTestPriorityEnum::Zero);
        $zeroStringDefault = $table->stringCastsEnum('flag', '0');

        $this->assertSame('status_enum', $noDefault->name);
        $this->assertSame('string', $noDefault->type);
        $this->assertNull($noDefault->default);

        $this->assertSame('phase_enum', $stringEnumDefault->name);
        $this->assertSame('draft', $stringEnumDefault->default);

        $this->assertSame('code_enum', $zeroEnumDefault->name);
        $this->assertSame('0', $zeroEnumDefault->default);

        $this->assertSame('priority_enum', $intEnumDefault->name);
        $this->assertSame(0, $intEnumDefault->default);

        $this->assertSame('flag_enum', $zeroStringDefault->name);
        $this->assertSame('0', $zeroStringDefault->default);
    }

    #[Test]
    public function it_creates_string_casts_array_columns(): void
    {
        $column = $this->blueprint()->stringCastsArray('tags');

        $this->assertSame('tags_array', $column->name);
        $this->assertSame('string', $column->type);
    }

    #[Test]
    public function it_creates_gram_columns_with_default_and_custom_precision(): void
    {
        $table = $this->blueprint();

        $default = $table->gram('weight');
        $custom = $table->gram('heavy_weight', 16, 4);

        $this->assertSame('weight_gram', $default->name);
        $this->assertSame('decimal', $default->type);
        $this->assertSame(12, $default->total);
        $this->assertSame(7, $default->places);

        $this->assertSame('heavy_weight_gram', $custom->name);
        $this->assertSame('decimal', $custom->type);
        $this->assertSame(16, $custom->total);
        $this->assertSame(4, $custom->places);
    }

    #[Test]
    public function it_creates_regular_and_big_pcs_columns(): void
    {
        $table = $this->blueprint();

        $regular = $table->pcs('item');
        $big = $table->pcs('bulk_item', true);

        $this->assertSame('item_pcs', $regular->name);
        $this->assertSame('integer', $regular->type);
        $this->assertTrue($regular->unsigned);
        $this->assertFalse($regular->autoIncrement);

        $this->assertSame('bulk_item_pcs', $big->name);
        $this->assertSame('bigInteger', $big->type);
        $this->assertTrue($big->unsigned);
        $this->assertFalse($big->autoIncrement);
    }

    #[Test]
    public function it_creates_quantity_columns_with_optional_config_and_sizing(): void
    {
        $defaultTable = $this->blueprint();
        [$gram, $pcs] = $defaultTable->quantity();

        $this->assertSame('quantity_gram', $gram->name);
        $this->assertSame('decimal', $gram->type);
        $this->assertSame(12, $gram->total);
        $this->assertSame(7, $gram->places);
        $this->assertNull($gram->nullable);

        $this->assertSame('quantity_pcs', $pcs->name);
        $this->assertSame('integer', $pcs->type);
        $this->assertTrue($pcs->unsigned);
        $this->assertNull($pcs->nullable);

        $configuredTable = $this->blueprint();
        [$configuredGram, $configuredPcs] = $configuredTable->quantity(
            ['quantity_gram' => 'nullable', 'rest' => ['default' => 0]],
            15,
            true
        );

        $this->assertSame(15, $configuredGram->total);
        $this->assertTrue($configuredGram->nullable);
        $this->assertNull($configuredGram->default);

        $this->assertSame('bigInteger', $configuredPcs->type);
        $this->assertNull($configuredPcs->nullable);
        $this->assertSame(0, $configuredPcs->default);
    }

    #[Test]
    public function it_creates_unsigned_and_signed_percentage_columns(): void
    {
        $table = $this->blueprint();

        $unsigned = $table->percentage('yield');
        $signed = $table->percentage('variance', true, 8, 4);

        $this->assertSame('yield_percentage', $unsigned->name);
        $this->assertSame('decimal', $unsigned->type);
        $this->assertSame(6, $unsigned->total);
        $this->assertSame(2, $unsigned->places);
        $this->assertTrue($unsigned->unsigned);

        $this->assertSame('variance_percentage', $signed->name);
        $this->assertSame('decimal', $signed->type);
        $this->assertSame(8, $signed->total);
        $this->assertSame(4, $signed->places);
        $this->assertNull($signed->unsigned);
    }

    #[Test]
    public function it_creates_foreign_id_and_plural_foreign_ids_without_double_id_suffix(): void
    {
        Schema::create('stocks', function (BaseBlueprint $table) {
            $table->id();
        });
        Schema::create('plain_records', function (BaseBlueprint $table) {
            $table->id();
        });

        $capturedFk = null;
        $capturedPluralCols = null;

        Schema::create('transfers', function (Blueprint $table) use (&$capturedFk, &$capturedPluralCols) {
            $table->id();
            $capturedFk = $table->createForeignId(PlainRecord::class, config: 'nullable');
            $capturedPluralCols = $table->createForeignIds([
                Stock::class,
                'from_stock' => Stock::class,
                'to_stock_id' => Stock::class,
            ], ALL_NULLABLE);
        });

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $capturedFk);
        $this->assertCount(3, $capturedPluralCols);
        $this->assertSame(
            ['id', 'plain_record_id', 'stock_id', 'from_stock_id', 'to_stock_id'],
            Schema::getColumnListing('transfers')
        );

        foreach ($capturedPluralCols as $col) {
            $this->assertTrue($col->nullable);
        }

        $foreignKeys = Schema::getForeignKeys('transfers');
        $this->assertCount(4, $foreignKeys);

        $byColumn = [];
        foreach ($foreignKeys as $fk) {
            $byColumn[$fk['columns'][0]] = $fk;
        }

        $this->assertSame('plain_records', $byColumn['plain_record_id']['foreign_table']);
        $this->assertSame('stocks', $byColumn['stock_id']['foreign_table']);
        $this->assertSame('stocks', $byColumn['from_stock_id']['foreign_table']);
        $this->assertSame('stocks', $byColumn['to_stock_id']['foreign_table']);
    }

    #[Test]
    public function it_adds_complete_timestamp_columns(): void
    {
        Schema::create('timed_records', function (Blueprint $table) {
            $table->id();
            $table->completeTimestampColumns();
        });

        $this->assertSame(
            ['id', 'created_at', 'updated_at', 'deleted_at'],
            Schema::getColumnListing('timed_records')
        );

        $blueprint = $this->blueprint();
        $blueprint->completeTimestampColumns();
        foreach ($blueprint->getColumns() as $column) {
            $this->assertSame('timestamp', $column->type);
            $this->assertTrue($column->nullable);
        }
    }

    #[Test]
    public function it_creates_timestamp_columns_with_callable_string_and_array_configs(): void
    {
        $table = $this->blueprint();

        $plain = $table->createTimestamp('started');
        $viaCallable = $table->createTimestamp('approved', fn (ColumnDefinition $col) => $col->nullable()->useCurrent());
        $viaString = $table->createTimestamp('cancelled', 'nullable');
        $viaArray = $table->createTimestamp('archived', ['nullable', 'comment' => 'Archive timestamp']);

        $this->assertSame('started_at', $plain->name);
        $this->assertSame('timestamp', $plain->type);
        $this->assertNull($plain->nullable);

        $this->assertSame('approved_at', $viaCallable->name);
        $this->assertTrue($viaCallable->nullable);
        $this->assertTrue($viaCallable->useCurrent);

        $this->assertSame('cancelled_at', $viaString->name);
        $this->assertTrue($viaString->nullable);

        $this->assertSame('archived_at', $viaArray->name);
        $this->assertTrue($viaArray->nullable);
        $this->assertSame('Archive timestamp', $viaArray->comment);
    }

    #[Test]
    public function it_creates_boolean_col_with_token_suffix(): void
    {
        $column = $this->blueprint()->booleanCol('verified');

        $this->assertSame('verified_is', $column->name);
        $this->assertSame('boolean', $column->type);
    }

    #[Test]
    public function it_handles_plural_dynamic_calls_and_batch_configs(): void
    {
        $table = $this->blueprint();

        $strings = $table->strings(['first_name', 'last_name'], ALL_NULLABLE);
        $grams = $table->grams(['gross', 'net'], ['gross_gram' => 'nullable']);
        $quantities = $table->quantities([null], ALL_NULLABLE);
        $booleans = $table->booleans(['active', 'published'], ['all' => ['default' => false]]);
        $times = $table->times(['opening', 'closing'], ALL_NULLABLE);
        $enums = $table->stringCastsEnums(['draft' => 'state', 'open' => 'level']);

        $this->assertSame(['first_name', 'last_name'], array_map(fn (ColumnDefinition $c) => $c->name, $strings));
        $this->assertTrue($strings[0]->nullable);
        $this->assertTrue($strings[1]->nullable);

        $this->assertSame(['gross_gram', 'net_gram'], array_map(fn (ColumnDefinition $c) => $c->name, $grams));
        $this->assertTrue($grams[0]->nullable);
        $this->assertNull($grams[1]->nullable);

        $this->assertSame(['quantity_gram', 'quantity_pcs'], array_map(fn (ColumnDefinition $c) => $c->name, $quantities));
        $this->assertTrue($quantities[0]->nullable);
        $this->assertTrue($quantities[1]->nullable);

        $this->assertSame(['active_is', 'published_is'], array_map(fn (ColumnDefinition $c) => $c->name, $booleans));
        $this->assertSame('boolean', $booleans[0]->type);
        $this->assertFalse($booleans[0]->default);
        $this->assertFalse($booleans[1]->default);

        $this->assertSame(['opening_time', 'closing_time'], array_map(fn (ColumnDefinition $c) => $c->name, $times));
        $this->assertSame('time', $times[0]->type);
        $this->assertTrue($times[0]->nullable);
        $this->assertTrue($times[1]->nullable);

        $this->assertSame(['state_enum', 'level_enum'], array_map(fn (ColumnDefinition $c) => $c->name, $enums));
        $this->assertSame('draft', $enums[0]->default);
        $this->assertSame('open', $enums[1]->default);
    }

    #[Test]
    public function it_falls_back_to_chopping_trailing_s_and_respects_macros_and_unknown_methods(): void
    {
        Blueprint::macro('serie', function (string $column): ColumnDefinition {
            /** @var Blueprint $this */
            return $this->string("{$column}_serie");
        });

        $table = $this->blueprint();
        $series = $table->series(['alpha', 'beta'], ALL_NULLABLE);

        $this->assertSame(['alpha_serie', 'beta_serie'], array_map(fn (ColumnDefinition $c) => $c->name, $series));
        $this->assertTrue($series[0]->nullable);
        $this->assertTrue($series[1]->nullable);

        Blueprint::macro('strings', fn () => 'explicit-macro');
        $this->assertSame('explicit-macro', $table->strings());

        $this->expectException(BadMethodCallException::class);
        $table->completelyUnknownHelper();
    }

    #[Test]
    public function it_creates_virtual_viewed_id_columns_for_string_array_and_null_prefixes(): void
    {
        $orders = $this->blueprint('orders');
        $defaultOrderViewed = $orders->viewedId();
        $minusOrderViewed = $orders->viewedId(100, 'order_code_id');
        $zeroMinusViewed = $orders->viewedId(0, 'zero_minus_id');

        $this->assertSame('viewed_id', $defaultOrderViewed->name);
        $this->assertSame('string', $defaultOrderViewed->type);
        $this->assertSame("CONCAT_WS('', 'ORD-', id)", (string) $defaultOrderViewed->virtualAs);
        $this->assertNull($defaultOrderViewed->storedAs);

        $this->assertSame('order_code_id', $minusOrderViewed->name);
        $this->assertSame("CONCAT_WS('', 'ORD-', id - 100)", (string) $minusOrderViewed->virtualAs);

        $this->assertSame("CONCAT_WS('', 'ORD-', id)", (string) $zeroMinusViewed->virtualAs);

        $ledgers = $this->blueprint('ledgers');
        $defaultEnumLedger = $ledgers->viewedId();
        $customEnumLedger = $ledgers->viewedId(10, 'entry_code_id', 'entry_kind_enum');

        $expectedCaseDefault = "CONCAT_WS('', \nCASE type_enum\n  WHEN 'debit' THEN 'DB'\n  WHEN 'credit' THEN 'CR'\nEND, '-', id)";
        $expectedCaseCustom = "CONCAT_WS('', \nCASE entry_kind_enum\n  WHEN 'debit' THEN 'DB'\n  WHEN 'credit' THEN 'CR'\nEND, '-', id - 10)";

        $this->assertSame($expectedCaseDefault, (string) $defaultEnumLedger->virtualAs);
        $this->assertNull($defaultEnumLedger->storedAs);
        $this->assertSame('entry_code_id', $customEnumLedger->name);
        $this->assertSame($expectedCaseCustom, (string) $customEnumLedger->virtualAs);

        $tickets = $this->blueprint('tickets');
        $ticketViewed = $tickets->viewedId();
        $ticketMinusViewed = $tickets->viewedId(5, 'plain_viewed_id');

        $this->assertSame('id', (string) $ticketViewed->virtualAs);
        $this->assertNull($ticketViewed->storedAs);
        $this->assertSame('id - 5', (string) $ticketMinusViewed->virtualAs);

        $plainRecords = $this->blueprint('plain_records');
        $plainViewed = $plainRecords->viewedId();
        $this->assertSame('id', (string) $plainViewed->virtualAs);
    }

    #[Test]
    public function it_creates_stored_viewed_id_columns_when_source_column_is_not_id_and_computes_values_in_sqlite(): void
    {
        $capturedInvoiceCol = null;
        Schema::create('invoices', function (Blueprint $table) use (&$capturedInvoiceCol) {
            $table->id();
            $table->unsignedInteger('seq_no');
            $capturedInvoiceCol = $table->viewedId();
            $table->viewedId(10, 'adjusted_viewed_id');
        });

        $this->assertNull($capturedInvoiceCol->virtualAs);
        $this->assertSame("CONCAT_WS('', 'INV-', seq_no)", (string) $capturedInvoiceCol->storedAs);

        DB::table('invoices')->insert(['seq_no' => 42]);
        $invoiceRow = DB::table('invoices')->first();

        $this->assertSame('INV-42', $invoiceRow->viewed_id);
        $this->assertSame('INV-32', $invoiceRow->adjusted_viewed_id);

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('seq_no');
            $table->stringCastsEnum('type');
            $table->viewedId();
        });

        DB::table('stock_movements')->insert([
            ['seq_no' => 7, 'type_enum' => 'in'],
            ['seq_no' => 8, 'type_enum' => 'out'],
        ]);

        $movements = DB::table('stock_movements')->orderBy('id')->pluck('viewed_id')->all();
        $this->assertSame(['IN-7', 'OUT-8'], $movements);
    }

    #[Test]
    public function it_throws_logic_exception_when_viewed_id_cannot_resolve_model_class(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot resolve model class for table [missing_entities] under config 'eloquent-kompact.path.model'.");

        $this->blueprint('missing_entities')->viewedId();
    }
}
