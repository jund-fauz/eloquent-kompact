<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IsAllOfTest extends TestCase
{
    protected function tearDown(): void
    {
        Relation::morphMap([], false);

        parent::tearDown();
    }

    private function model(): IsAllOfModel
    {
        $model = (new IsAllOfModel)->forceFill(['note' => null, 'status' => 'draft', 'qty' => 5])->syncOriginal();
        $model->status = 'paid';

        return $model;
    }

    #[Test]
    public function it_checks_every_check_with_the_methods_of_the_model_and_the_functions(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAllOf('note', 'null', 'blank'));
        $this->assertFalse($model->isAllOf('note', 'null', 'filled'));
        $this->assertTrue($model->isAllOf('qty', 'int', 'numeric'));
        $this->assertTrue($model->isAllOf('note'));
    }

    #[Test]
    public function it_checks_at_least_one_check(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAnyOf('note', 'numeric', 'null'));
        $this->assertFalse($model->isAnyOf('qty', 'null', 'string'));
        $this->assertFalse($model->isAnyOf('note'));
    }

    #[Test]
    public function it_checks_all_model_checks_with_match_all(): void
    {
        $model = $this->model();

        $this->assertTrue($model->matchAll(isDirty: 'status', filled: 'status'));
        $this->assertFalse($model->matchAll(isDirty: 'status', isClean: 'status'));
        $this->assertTrue($model->matchAll('isDirty'));
        $this->assertTrue($model->matchAll());
    }

    #[Test]
    public function it_checks_at_least_one_model_check_with_any(): void
    {
        $model = $this->model();

        $this->assertTrue($model->any(isClean: 'note', isDirty: 'status'));
        $this->assertFalse($model->any(isClean: 'status'));
        $this->assertFalse($model->any());
    }

    #[Test]
    public function it_finds_a_bool_method_without_a_native_return_type_by_its_docblock(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAllOf('status', 'dirty', 'filled'));
        $this->assertFalse($model->isAllOf('qty', 'dirty'));
        $this->assertTrue($model->isAllOf('qty', 'clean'));
        $this->assertTrue($model->isAllOf('qty', 'docblock_only'));
    }

    #[Test]
    public function it_ignores_a_method_that_does_not_return_a_bool(): void
    {
        $this->assertTrue($this->model()->isAllOf('qty', 'int'));
    }

    #[Test]
    public function it_passes_the_parameters_of_a_named_argument(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAnyOf('status', in: 'paid,sent'));
        $this->assertFalse($model->isAllOf('status', 'dirty', in: 'draft,sent'));
    }

    #[Test]
    public function it_passes_an_array_of_parameters_of_a_named_argument(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAllOf('qty', sameWith: [5]));
        $this->assertTrue($model->isAllOf('qty', sameWith: ['5', 'integer']));
    }

    #[Test]
    public function it_passes_the_argument_of_a_method_that_does_not_take_a_column(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAllOf(['status' => 'paid'], 'match'));
        $this->assertFalse($model->isAllOf(['status' => 'draft'], 'match'));
    }

    #[Test]
    public function it_negates_a_check(): void
    {
        $model = $this->model();

        $this->assertTrue($model->not('null', 'status'));
        $this->assertFalse($model->not('null', 'note'));
        $this->assertTrue($model->not('in', 'status', 'draft,sent'));
    }

    #[Test]
    public function it_checks_one_check_on_every_column(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAll('filled', 'status', 'qty'));
        $this->assertFalse($model->isAll('filled', 'status', 'note'));
        $this->assertTrue($model->isAll('numeric', ['qty'], [['qty']]));
        $this->assertFalse($model->isAll('dirty', 'status', 'qty'));
        $this->assertTrue($model->isAll('dirty'));
    }

    #[Test]
    public function it_checks_one_check_on_at_least_one_column(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAny('null', 'status', 'note'));
        $this->assertFalse($model->isAny('null', ['status', 'qty']));
        $this->assertTrue($model->isAny('dirty', 'qty', 'status'));
        $this->assertFalse($model->isAny('dirty'));
    }

    #[Test]
    public function it_checks_that_one_check_is_false_on_every_column(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAllNot('null', 'status', 'qty'));
        $this->assertFalse($model->isAllNot('null', 'status', 'note'));
        $this->assertTrue($model->isAllNot('dirty', 'qty', 'note'));
        $this->assertTrue($model->isAllNot('dirty'));
    }

    #[Test]
    public function it_checks_that_one_check_is_false_on_at_least_one_column(): void
    {
        $model = $this->model();

        $this->assertTrue($model->isAnyNot('filled', 'status', 'note'));
        $this->assertFalse($model->isAnyNot('filled', 'status', 'qty'));
        $this->assertFalse($model->isAnyNot('dirty'));
    }

    #[Test]
    public function it_flattens_the_columns_of_the_negated_checks(): void
    {
        $model = $this->model();

        $this->assertFalse($model->isAllNot('null', ['status', ['note']]));
        $this->assertTrue($model->isAnyNot('filled', ['status', ['note']]));
    }

    #[Test]
    public function it_takes_a_logic_word_as_the_last_column_of_same_with_other(): void
    {
        $model = (new IsAllOfModel)->forceFill(['a' => 1, 'b' => 2]);
        $other = (new IsAllOfModel)->forceFill(['a' => 1, 'b' => 3]);

        $this->assertFalse($model->sameWithOther($other, 'a', 'b'));
        $this->assertFalse($model->sameWithOther($other, 'b', 'a'));
        $this->assertFalse($model->sameWithOther($other, 'a', 'b', 'and'));
        $this->assertFalse($model->sameWithOther($other, 'a', 'b', 'all'));
        $this->assertTrue($model->sameWithOther($other, 'a', 'b', 'or'));
        $this->assertTrue($model->sameWithOther($other, 'b', 'a', 'any'));
        $this->assertTrue($model->sameWithOther($other, 'a'));
        $this->assertFalse($model->sameWithOther($other, 'b', 'or'));
        $this->assertFalse($model->sameWithOther($other));
    }

    #[Test]
    public function it_checks_dirty_with_same_with_and_not_same_with(): void
    {
        $model = (new IsAllOfModel)->forceFill(['a' => 1, 'b' => 2])->syncOriginal();
        $this->assertFalse($model->isAllOf('a', 'dirty', sameWith: [1]));
        $this->assertFalse($model->isAllOf('a', 'dirty', not_same_with: [5]));

        $model->a = 5;
        $this->assertTrue($model->isAllOf('a', 'dirty', sameWith: [5]));
        $this->assertFalse($model->isAllOf('a', 'dirty', sameWith: [1]));
        $this->assertFalse($model->isAllOf('a', 'dirty', sameWith: ['5']));
        $this->assertTrue($model->isAllOf('a', 'dirty', not_same_with: [1]));
        $this->assertFalse($model->isAllOf('a', 'dirty', not_same_with: [5]));
    }

    #[Test]
    public function it_checks_dirty_with_blank_filled_null_and_not_null(): void
    {
        $model = (new IsAllOfModel)->forceFill(['a' => 1, 'note' => 'x'])->syncOriginal();
        $this->assertFalse($model->isAllOf('note', 'dirty', 'blank'));
        $this->assertFalse($model->isAllOf('note', 'dirty', 'filled'));
        $this->assertFalse($model->isAllOf('a', 'dirty', 'not_null'));
        $this->assertFalse((new IsAllOfModel)->forceFill(['note' => ''])->syncOriginal()->isAllOf('note', 'dirty', 'blank'));
        $this->assertFalse((new IsAllOfModel)->forceFill(['note' => null])->syncOriginal()->isAllOf('note', 'dirty', 'null'));

        $model->note = '   ';
        $this->assertTrue($model->isAllOf('note', 'dirty', 'blank'));
        $this->assertFalse($model->isAllOf('note', 'dirty', 'filled'));

        $model->note = '0';
        $this->assertFalse($model->isAllOf('note', 'dirty', 'blank'));
        $this->assertTrue($model->isAllOf('note', 'dirty', 'filled'));

        $model->note = 'y';
        $this->assertFalse($model->isAllOf('note', 'dirty', 'blank'));
        $this->assertTrue($model->isAllOf('note', 'dirty', 'filled'));

        $model->a = null;
        $this->assertTrue($model->isAllOf('a', 'dirty', 'null'));
        $this->assertFalse($model->isAllOf('a', 'dirty', 'not_null'));

        $model->a = 5;
        $this->assertFalse($model->isAllOf('a', 'dirty', 'null'));
        $this->assertTrue($model->isAllOf('a', 'dirty', 'not_null'));
    }

    #[Test]
    public function it_checks_dirty_with_morph_type_and_morph_map(): void
    {
        $type = ColumnCreator::type('owner');
        $model = (new IsAllOfModel)->forceFill([$type => IsAllOfModel::class])->syncOriginal();
        $this->assertFalse($model->isAllOf($type, 'dirty', sameWith: [Relation::getMorphAlias(IsAllOfModel::class)]));

        $model->{$type} = IsAllOfOther::class;
        $this->assertTrue($model->isAllOf($type, 'dirty', sameWith: [Relation::getMorphAlias(IsAllOfOther::class)]));
        $this->assertFalse($model->isAllOf($type, 'dirty', sameWith: [Relation::getMorphAlias(IsAllOfModel::class)]));

        Relation::morphMap(['other' => IsAllOfOther::class], false);
        $this->assertFalse($model->isAllOf($type, 'dirty', sameWith: [Relation::getMorphAlias(IsAllOfOther::class)]));

        $model->{$type} = 'other';
        $this->assertTrue($model->isAllOf($type, 'dirty', sameWith: [Relation::getMorphAlias(IsAllOfOther::class)]));
    }

    #[Test]
    public function it_is_not_dirty_when_the_value_goes_back(): void
    {
        $model = (new IsAllOfModel)->forceFill(['a' => 1])->syncOriginal();
        $model->a = 5;
        $model->a = 1;

        $this->assertFalse($model->isAllOf('a', 'dirty', sameWith: [1]));
    }

    #[Test]
    public function it_treats_every_attribute_of_a_new_model_as_dirty(): void
    {
        $model = (new IsAllOfModel)->forceFill(['a' => 1]);

        $this->assertTrue($model->isAllOf('a', 'dirty', sameWith: [1]));
    }

    #[Test]
    public function it_is_not_dirty_after_saving(): void
    {
        $this->createLifecycleTable();

        $model = (new IsAllOfLifecycleModel)->forceFill(['a' => 1]);
        $model->save();

        $this->assertFalse($model->isAllOf('a', 'dirty', sameWith: [1]));
    }

    #[Test]
    public function it_is_still_dirty_in_the_saved_event(): void
    {
        $this->createLifecycleTable();

        $results = [];
        IsAllOfLifecycleModel::saving(function (IsAllOfLifecycleModel $model) use (&$results) {
            $results['saving'] = $model->isAllOf('a', 'dirty', sameWith: [1]);
        });
        IsAllOfLifecycleModel::saved(function (IsAllOfLifecycleModel $model) use (&$results) {
            $results['saved'] = $model->isAllOf('a', 'dirty', sameWith: [1]);
        });

        try {
            (new IsAllOfLifecycleModel)->forceFill(['a' => 1])->save();
        } finally {
            IsAllOfLifecycleModel::flushEventListeners();
        }

        $this->assertSame(['saving' => true, 'saved' => true], $results);
    }

    #[Test]
    public function it_checks_was_changed_across_columns_during_model_persistence(): void
    {
        $this->createLifecycleTable();

        $model = IsAllOfLifecycleModel::create(['a' => 1, 'b' => 1]);
        $this->assertFalse($model->isAll('was_changed', 'a', 'b'));
        $this->assertTrue($model->isAllNot('was_changed', 'a', 'b'));

        // Unsaved dirty changes do not count as was_changed
        $model->a = 2;
        $this->assertFalse($model->isAll('was_changed', 'a'));
        $this->assertTrue($model->isAllNot('was_changed', 'a'));

        // Saving updates was_changed, and a subsequent no-op save keeps the last update's changes
        $model->save();
        $this->assertTrue($model->isAll('was_changed', 'a'));
        $this->assertFalse($model->isAll('was_changed', 'b'));
        $this->assertFalse($model->isAll('was_changed', 'a', 'b'));
        $this->assertFalse($model->isAllNot('was_changed', 'a'));
        $this->assertTrue($model->isAllNot('was_changed', 'b'));
        $this->assertFalse($model->isAllNot('was_changed', 'a', 'b'));

        $model->save();
        $this->assertTrue($model->isAll('was_changed', 'a'));

        $model->update(['a' => 3, 'b' => 3]);
        $this->assertTrue($model->isAll('was_changed', 'a', 'b'));
        $this->assertFalse($model->isAllNot('was_changed', 'a', 'b'));
    }

    #[Test]
    public function it_has_the_check_methods_on_the_kompact_models(): void
    {
        $methods = [
            'matchAll', 'any', 'not', 'if',
            'isAll', 'isAny', 'isAllNot', 'isAnyNot',
            'isAllOf', 'isAnyOf',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(method_exists(KompactModel::class, $method), "KompactModel::$method");
            $this->assertTrue(method_exists(KompactPivot::class, $method), "KompactPivot::$method");
        }
    }

    #[Test]
    public function it_throws_for_an_unknown_check(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->model()->isAllOf('qty', 'kompact_missing_check');
    }

    private function createLifecycleTable(): void
    {
        Schema::create('is_all_of_lifecycle_models', function (Blueprint $table) {
            $table->id();
            $table->integer('a')->nullable();
            $table->integer('b')->nullable();
        });
    }
}

class IsAllOfModel extends Model
{
    use AdditionalQueries;

    /**
     * @param string $column
     * @return bool
     */
    public function isDocblockOnly(string $column)
    {
        return $this->{$column} === 5;
    }

    public function int(string $column): string
    {
        return 'not a bool';
    }
}

class IsAllOfLifecycleModel extends Model
{
    use AdditionalQueries;

    protected $table = 'is_all_of_lifecycle_models';

    protected $guarded = [];

    public $timestamps = false;
}

class IsAllOfOther extends Model {}
