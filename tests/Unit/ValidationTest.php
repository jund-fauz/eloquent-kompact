<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Unique;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ColumnListing::flush();
        KompactModel::flushKompactCache();
        KompactPivot::flushKompactCache();

        Schema::create('val_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('note')->nullable();
            $table->foreignId(ColumnCreator::relation('user'))->nullable();
            $table->decimal(ColumnCreator::gram('weight'))->nullable();
            $table->unsignedInteger(ColumnCreator::pcs('quantity'))->nullable();
            $table->decimal(ColumnCreator::percentage('discount'))->nullable();
            $table->boolean(ColumnCreator::boolean('active'))->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        ColumnListing::flush();
        KompactModel::flushKompactCache();
        KompactPivot::flushKompactCache();

        parent::tearDown();
    }

    #[Test]
    public function it_applies_the_default_rules_to_every_fillable_column(): void
    {
        $this->assertSame([
            'name' => [],
            'note' => [],
            ColumnCreator::relation('user') => ['integer'],
            ColumnCreator::gram('weight') => ['numeric', 'min:0.001', 'decimal:0,3', 'max:99999.9999999'],
            ColumnCreator::pcs('quantity') => ['integer', 'min:0', 'max:4294967295'],
            ColumnCreator::percentage('discount') => ['numeric', 'min:0', 'max:100'],
            ColumnCreator::boolean('active') => ['boolean'],
        ], ValidationItem::rules());
    }

    #[Test]
    public function it_merges_the_rules_of_every_layer_without_overriding_each_other(): void
    {
        $rules = ValidationChildItem::rules();

        $this->assertSame(['required', 'string', 'max:50', 'min:3'], $rules['name']);
        $this->assertSame(['nullable', 'string'], array_slice($rules['note'], 0, 2));
        $this->assertSame('max:255', $rules['note'][3]);
        $this->assertSame(['required', 'integer'], $rules[ColumnCreator::relation('user')]);
        $this->assertSame(['integer', 'max:10', 'min:1'], $rules[ColumnCreator::pcs('quantity')]);
    }

    #[Test]
    public function it_applies_the_rules_of_an_ancestor_only_to_the_columns_of_the_model(): void
    {
        $this->assertArrayNotHasKey('phone', ValidationChildItem::rules());
    }

    #[Test]
    public function it_keeps_the_columns_declared_by_the_model_itself(): void
    {
        $this->assertSame(['required', 'confirmed'], ValidationChildItem::rules()['password']);
    }

    #[Test]
    public function it_resolves_the_rules_of_an_ancestor_against_the_model(): void
    {
        $record = new ValidationChildItem;
        $record->id = 5;

        $rule = ValidationChildItem::rules($record)['note'][2] ?? null;

        $this->assertInstanceOf(Unique::class, $rule);
        $this->assertSame('unique:val_items,note,"5",id', (string) $rule);
    }

    #[Test]
    public function it_uses_the_overridden_max_rules(): void
    {
        $rules = ValidationGrandchildItem::rules();

        $this->assertContains('max:9', $rules[ColumnCreator::gram('weight')]);
        $this->assertSame(['required', 'string', 'max:50', 'min:3'], $rules['name']);
        $this->assertArrayNotHasKey('password', $rules);
    }

    #[Test]
    public function it_applies_the_default_rules_to_a_pivot(): void
    {
        $this->assertSame(['integer'], ValidationPivotItem::rules()[ColumnCreator::relation('user')]);
        $this->assertSame(['boolean'], ValidationPivotItem::rules()[ColumnCreator::boolean('active')]);
    }

    #[Test]
    public function it_flushes_the_fillable_cache_of_a_pivot(): void
    {
        $this->assertNotContains('extra', (new ValidationPivotItem)->getFillable());

        Schema::table('val_items', fn (Blueprint $table) => $table->string('extra')->nullable());
        ColumnListing::flush();
        KompactPivot::flushKompactCache();

        $this->assertContains('extra', (new ValidationPivotItem)->getFillable());
    }

    #[Test]
    public function it_accepts_the_rules_as_a_string(): void
    {
        $this->assertSame(['required', 'email', 'max:255'], ValidationStringItem::rules()['note']);
    }
}

class ValidationItem extends KompactModel
{
    protected $table = 'val_items';
}

abstract class ValidationBaseItem extends KompactModel
{
    protected $table = 'val_items';

    protected function defineRules(?Model $record = null): array
    {
        return [
            'name' => ['string', 'min:3', 'max:100'],
            'note' => ['nullable', 'string', static::uniqueRule('note', $record)],
            'phone' => ['string'],
            ColumnCreator::pcs('quantity') => ['min:1'],
        ];
    }
}

class ValidationChildItem extends ValidationBaseItem
{
    protected function defineRules(?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            ColumnCreator::relation('user') => 'required',
            ColumnCreator::pcs('quantity') => ['integer', 'max:10'],
            'password' => ['required', 'confirmed'],
        ];
    }
}

class ValidationGrandchildItem extends ValidationChildItem
{
    protected static function gramMaxRule(): float
    {
        return 9;
    }
}

class ValidationStringItem extends KompactModel
{
    protected $table = 'val_items';

    protected function defineRules(?Model $record = null): array
    {
        return ['note' => 'required|email|max:255'];
    }
}

class ValidationPivotItem extends KompactPivot
{
    protected $table = 'val_items';
}
