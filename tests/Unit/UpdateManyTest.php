<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TypeError;

class UpdateManyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('update_many_models', function (Blueprint $table) {
            $table->id();
            $table->integer('a')->nullable();
            $table->integer('b')->nullable();
        });
    }

    private function created(): UpdateManyModel
    {
        return UpdateManyModel::create(['a' => 1, 'b' => 2]);
    }

    private function assertRow(UpdateManyModel $model, ?int $a, ?int $b): void
    {
        $this->assertSame(['a' => $a, 'b' => $b], UpdateManyModel::find($model->id)->only('a', 'b'));
    }

    #[Test]
    public function it_updates_with_the_merged_arrays(): void
    {
        $model = $this->created();

        $this->assertTrue($model->updateMany(['a' => 5], ['b' => 6]));
        $this->assertRow($model, 5, 6);
    }

    #[Test]
    public function it_lets_a_later_array_win(): void
    {
        $model = $this->created();

        $this->assertTrue($model->updateMany(['a' => 5, 'b' => 6], ['a' => 9]));
        $this->assertRow($model, 9, 6);
    }

    #[Test]
    public function it_updates_with_one_array(): void
    {
        $model = $this->created();

        $this->assertTrue($model->updateMany(['a' => 5]));
        $this->assertRow($model, 5, 2);
    }

    #[Test]
    public function it_can_set_a_column_to_null(): void
    {
        $model = $this->created();

        $this->assertTrue($model->updateMany(['a' => null], ['b' => 6]));
        $this->assertRow($model, null, 6);
    }

    #[Test]
    public function it_ignores_empty_arrays(): void
    {
        $model = $this->created();

        $this->assertTrue($model->updateMany(['a' => 5], []));
        $this->assertRow($model, 5, 2);
    }

    #[Test]
    public function it_changes_nothing_without_data(): void
    {
        $model = $this->created();

        $this->assertTrue($model->updateMany());
        $this->assertTrue($model->updateMany([]));
        $this->assertRow($model, 1, 2);
    }

    #[Test]
    public function it_returns_false_and_changes_nothing_for_a_list(): void
    {
        $model = $this->created();

        $this->assertFalse($model->updateMany([7, 8]));
        $this->assertFalse($model->updateMany(['a' => 5], [7]));
        $this->assertRow($model, 1, 2);
    }

    #[Test]
    public function it_only_takes_arrays(): void
    {
        $this->expectException(TypeError::class);

        $this->created()->updateMany(['a' => 5], null);
    }

    #[Test]
    public function it_takes_named_arguments(): void
    {
        $model = $this->created();

        $this->assertTrue($model->updateMany(first: ['a' => 5], second: ['b' => 6]));
        $this->assertRow($model, 5, 6);
    }

    #[Test]
    public function it_respects_the_fillable_columns(): void
    {
        $model = $this->created();

        $this->assertTrue($model->updateMany(['a' => 5], ['id' => 99]));
        $this->assertRow($model, 5, 2);
    }

    #[Test]
    public function it_returns_false_on_a_model_that_is_not_saved(): void
    {
        $model = new UpdateManyModel;

        $this->assertFalse($model->updateMany(['a' => 5]));
        $this->assertFalse($model->exists);
    }

    #[Test]
    public function it_has_the_method_on_the_kompact_models(): void
    {
        $this->assertTrue(method_exists(KompactModel::class, 'updateMany'));
        $this->assertTrue(method_exists(KompactPivot::class, 'updateMany'));
    }
}

class UpdateManyModel extends Model
{
    use AdditionalQueries;

    protected $table = 'update_many_models';

    protected $fillable = ['a', 'b'];

    public $timestamps = false;
}
