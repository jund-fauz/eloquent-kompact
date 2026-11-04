<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AndDirtyTest extends TestCase
{
    private function saved(array $attributes = []): AndDirtyModel
    {
        return (new AndDirtyModel)->forceFill($attributes + ['a' => 1, 'b' => 2, 'note' => null])->syncOriginal();
    }

    private function other(): AndDirtyModel
    {
        return (new AndDirtyModel)->forceFill(['a' => 10, 'b' => 20]);
    }

    protected function tearDown(): void
    {
        Relation::morphMap([], false);

        parent::tearDown();
    }

    #[Test]
    public function it_checks_same_with_and_dirty(): void
    {
        $model = $this->saved();
        $this->assertFalse($model->sameWithAndDirty('a', 1));

        $model->a = 5;
        $this->assertTrue($model->sameWithAndDirty('a', 5));
        $this->assertFalse($model->sameWithAndDirty('a', 1));
        $this->assertFalse($model->sameWithAndDirty('a', '5'));
    }

    #[Test]
    public function it_checks_not_same_with_and_dirty(): void
    {
        $model = $this->saved();
        $this->assertFalse($model->notSameWithAndDirty('a', 5));

        $model->a = 5;
        $this->assertTrue($model->notSameWithAndDirty('a', 1));
        $this->assertFalse($model->notSameWithAndDirty('a', 5));
    }

    #[Test]
    public function it_needs_every_column_to_be_dirty_and_the_same(): void
    {
        $model = $this->saved();
        $model->a = 10;
        $this->assertFalse($model->sameWithOtherAndDirty($this->other(), 'a', 'b'));

        $model->b = 3;
        $this->assertFalse($model->sameWithOtherAndDirty($this->other(), 'a', 'b'));

        $model->b = 20;
        $this->assertTrue($model->sameWithOtherAndDirty($this->other(), 'a', 'b'));
        $this->assertFalse($model->sameWithOtherAndDirty($this->other()));
    }

    #[Test]
    public function it_does_not_count_a_same_column_that_is_not_dirty(): void
    {
        $model = $this->saved(['a' => 10]);
        $model->b = 20;

        $this->assertTrue($model->sameWithOther($this->other(), 'a', 'b'));
        $this->assertFalse($model->sameWithOtherAndDirty($this->other(), 'a', 'b'));
    }

    #[Test]
    public function it_needs_one_column_to_be_dirty_and_the_same(): void
    {
        $model = $this->saved(['a' => 10]);
        $model->b = 3;
        $this->assertFalse($model->sameWithOtherOrAndDirty($this->other(), 'a', 'b'));

        $model->b = 20;
        $this->assertTrue($model->sameWithOtherOrAndDirty($this->other(), 'a', 'b'));
        $this->assertFalse($model->sameWithOtherOrAndDirty($this->other()));
    }

    #[Test]
    public function it_needs_every_column_to_be_dirty_and_different(): void
    {
        $model = $this->saved();
        $model->a = 5;
        $this->assertFalse($model->notSameWithOtherAndDirty($this->other(), 'a', 'b'));

        $model->b = 6;
        $this->assertTrue($model->notSameWithOtherAndDirty($this->other(), 'a', 'b'));

        $model->b = 20;
        $this->assertFalse($model->notSameWithOtherAndDirty($this->other(), 'a', 'b'));
        $this->assertFalse($model->notSameWithOtherAndDirty($this->other()));
    }

    #[Test]
    public function it_needs_one_column_to_be_dirty_and_different(): void
    {
        $model = $this->saved();
        $this->assertFalse($model->notSameWithOtherOrAndDirty($this->other(), 'a', 'b'));

        $model->a = 5;
        $this->assertTrue($model->notSameWithOtherOrAndDirty($this->other(), 'a', 'b'));

        $model->a = 10;
        $this->assertFalse($model->notSameWithOtherOrAndDirty($this->other(), 'a', 'b'));
        $this->assertFalse($model->notSameWithOtherOrAndDirty($this->other()));
    }

    #[Test]
    public function it_matches_only_dirty_columns(): void
    {
        $model = $this->saved();
        $this->assertFalse($model->matchAndDirty(['a' => 1]));

        $model->a = 5;
        $this->assertTrue($model->matchAndDirty(['a' => 5]));
        $this->assertTrue($model->matchAndDirty(['a' => [4, 5]]));
        $this->assertFalse($model->matchAndDirty(['a' => 6]));
        $this->assertFalse($model->matchAndDirty(['a' => 5, 'b' => 2]));
        $this->assertFalse($model->matchAndDirty([]));
    }

    #[Test]
    public function it_checks_the_morph_model_and_dirty(): void
    {
        $model = $this->saved(['owner_type' => AndDirtyModel::class]);
        $this->assertFalse($model->isMorphModelSameAndDirty('owner', AndDirtyModel::class));

        $model->owner_type = AndDirtyOther::class;
        $this->assertTrue($model->isMorphModelSameAndDirty('owner', AndDirtyOther::class));
        $this->assertFalse($model->isMorphModelSameAndDirty('owner', AndDirtyModel::class));

        Relation::morphMap(['other' => AndDirtyOther::class], false);
        $this->assertFalse($model->isMorphModelSameAndDirty('owner', AndDirtyOther::class));

        $model->owner_type = 'other';
        $this->assertTrue($model->isMorphModelSameAndDirty('owner', AndDirtyOther::class));
    }

    #[Test]
    public function it_checks_empty_and_dirty(): void
    {
        $model = $this->saved(['note' => 'x']);
        $this->assertFalse($model->isEmptyAndDirty('note'));

        $model->note = '0';
        $this->assertTrue($model->isEmptyAndDirty('note'));

        $model->note = 'y';
        $this->assertFalse($model->isEmptyAndDirty('note'));

        $this->assertFalse($this->saved(['note' => ''])->isEmptyAndDirty('note'));
    }

    #[Test]
    public function it_checks_filled_and_dirty(): void
    {
        $model = $this->saved(['note' => 'x']);
        $this->assertFalse($model->isFilledAndDirty('note'));

        $model->note = 'y';
        $this->assertTrue($model->isFilledAndDirty('note'));

        $model->note = ' ';
        $this->assertFalse($model->isFilledAndDirty('note'));
    }

    #[Test]
    public function it_checks_null_and_dirty(): void
    {
        $model = $this->saved();
        $this->assertFalse($model->isNullAndDirty('note'));

        $model->a = null;
        $this->assertTrue($model->isNullAndDirty('a'));

        $model->a = 5;
        $this->assertFalse($model->isNullAndDirty('a'));
    }

    #[Test]
    public function it_checks_not_null_and_dirty(): void
    {
        $model = $this->saved();
        $this->assertFalse($model->isNotNullAndDirty('a'));

        $model->a = 5;
        $this->assertTrue($model->isNotNullAndDirty('a'));

        $model->a = null;
        $this->assertFalse($model->isNotNullAndDirty('a'));
    }

    #[Test]
    public function it_is_not_dirty_when_the_value_goes_back(): void
    {
        $model = $this->saved();
        $model->a = 5;
        $model->a = 1;

        $this->assertFalse($model->sameWithAndDirty('a', 1));
    }

    #[Test]
    public function it_treats_every_attribute_of_a_new_model_as_dirty(): void
    {
        $model = (new AndDirtyModel)->forceFill(['a' => 1]);

        $this->assertTrue($model->sameWithAndDirty('a', 1));
    }

    #[Test]
    public function it_is_not_dirty_after_saving(): void
    {
        Schema::create('and_dirty_models', function (Blueprint $table) {
            $table->id();
            $table->integer('a');
        });

        $model = (new AndDirtyModel)->forceFill(['a' => 1]);
        $model->save();

        $this->assertFalse($model->sameWithAndDirty('a', 1));
    }

    #[Test]
    public function it_is_still_dirty_in_the_saved_event(): void
    {
        Schema::create('and_dirty_models', function (Blueprint $table) {
            $table->id();
            $table->integer('a');
        });

        $results = [];
        AndDirtyModel::saving(function (AndDirtyModel $model) use (&$results) {
            $results['saving'] = $model->sameWithAndDirty('a', 1);
        });
        AndDirtyModel::saved(function (AndDirtyModel $model) use (&$results) {
            $results['saved'] = $model->sameWithAndDirty('a', 1);
        });

        try {
            (new AndDirtyModel)->forceFill(['a' => 1])->save();
        } finally {
            AndDirtyModel::flushEventListeners();
        }

        $this->assertSame(['saving' => true, 'saved' => true], $results);
    }

    #[Test]
    public function it_has_the_methods_on_the_kompact_models(): void
    {
        $methods = [
            'sameWithAndDirty', 'notSameWithAndDirty',
            'sameWithOtherAndDirty', 'sameWithOtherOrAndDirty', 'notSameWithOtherAndDirty', 'notSameWithOtherOrAndDirty',
            'matchAndDirty', 'isMorphModelSameAndDirty',
            'isEmptyAndDirty', 'isFilledAndDirty', 'isNullAndDirty', 'isNotNullAndDirty',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(method_exists(KompactModel::class, $method), "KompactModel::$method");
            $this->assertTrue(method_exists(KompactPivot::class, $method), "KompactPivot::$method");
        }
    }
}

class AndDirtyModel extends Model
{
    use AdditionalQueries;

    public $timestamps = false;
}

class AndDirtyOther extends Model {}
