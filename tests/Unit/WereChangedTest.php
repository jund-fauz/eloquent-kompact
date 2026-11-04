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

class WereChangedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('were_changed_models', function (Blueprint $table) {
            $table->id();
            $table->integer('a')->nullable();
            $table->integer('b')->nullable();
        });
    }

    private function created(): WereChangedModel
    {
        return WereChangedModel::create(['a' => 1, 'b' => 1]);
    }

    #[Test]
    public function it_needs_every_column_to_be_changed(): void
    {
        $model = $this->created();

        $model->update(['a' => 2]);
        $this->assertTrue($model->wereChanged('a'));
        $this->assertFalse($model->wereChanged('b'));
        $this->assertFalse($model->wereChanged('a', 'b'));

        $model->update(['a' => 3, 'b' => 3]);
        $this->assertTrue($model->wereChanged('a', 'b'));
    }

    #[Test]
    public function it_needs_every_column_to_not_be_changed(): void
    {
        $model = $this->created();

        $model->update(['a' => 2]);
        $this->assertFalse($model->wereNotChanged('a'));
        $this->assertTrue($model->wereNotChanged('b'));
        $this->assertFalse($model->wereNotChanged('a', 'b'));

        $model->update(['a' => 3, 'b' => 3]);
        $this->assertFalse($model->wereNotChanged('a', 'b'));
    }

    #[Test]
    public function it_reads_any_column_without_arguments(): void
    {
        $model = $this->created();

        $fresh = WereChangedModel::find($model->id);
        $this->assertFalse($fresh->wereChanged());
        $this->assertTrue($fresh->wereNotChanged());

        $model->update(['a' => 2]);
        $this->assertTrue($model->wereChanged());
        $this->assertFalse($model->wereNotChanged());
    }

    #[Test]
    public function it_sees_no_change_right_after_create(): void
    {
        $model = $this->created();

        $this->assertFalse($model->wereChanged('a', 'b'));
        $this->assertTrue($model->wereNotChanged('a', 'b'));
    }

    #[Test]
    public function it_keeps_the_changes_of_the_last_update_after_a_save_without_changes(): void
    {
        $model = $this->created();

        $model->update(['a' => 2]);
        $model->save();

        $this->assertTrue($model->wereChanged('a'));
    }

    #[Test]
    public function it_is_false_for_a_column_that_is_changed_but_not_saved(): void
    {
        $model = $this->created();

        $model->a = 2;

        $this->assertFalse($model->wereChanged('a'));
        $this->assertTrue($model->wereNotChanged('a'));
    }

    #[Test]
    public function it_has_the_methods_on_the_kompact_models(): void
    {
        foreach (['wereChanged', 'wereNotChanged'] as $method) {
            $this->assertTrue(method_exists(KompactModel::class, $method), "KompactModel::$method");
            $this->assertTrue(method_exists(KompactPivot::class, $method), "KompactPivot::$method");
        }
    }
}

class WereChangedModel extends Model
{
    use AdditionalQueries;

    protected $table = 'were_changed_models';

    protected $guarded = [];

    public $timestamps = false;
}
