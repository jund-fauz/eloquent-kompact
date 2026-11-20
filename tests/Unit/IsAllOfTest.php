<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IsAllOfTest extends TestCase
{
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
    public function it_gives_the_same_result_as_the_deprecated_were_changed_methods(): void
    {
        foreach ([[], ['a'], ['b'], ['a', 'b']] as $changed) {
            $m = (new IsAllOfModel)->forceFill(['a' => 1, 'b' => 2])->syncOriginal();
            foreach ($changed as $column) {
                $m->{$column} = 9;
            }
            $m->syncChanges();
            $state = 'changed: ' . implode(', ', $changed);

            foreach ([['a'], ['b'], ['a', 'b']] as $columns) {
                $this->assertSame($m->wereChanged(...$columns), $m->isAll('was_changed', ...$columns), $state);
                $this->assertSame($m->wereNotChanged(...$columns), $m->isAllNot('was_changed', ...$columns), $state);
            }
            $this->assertSame($m->wereChanged(), $m->wasChanged(), $state);
            $this->assertSame($m->wereNotChanged(), ! $m->wasChanged(), $state);
        }
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
    public function it_gives_the_same_result_as_the_deprecated_same_with_other_methods(): void
    {
        $model = (new IsAllOfModel)->forceFill(['a' => 1, 'b' => 2]);

        foreach ([[1, 2], [1, 3], [0, 2], [0, 3]] as [$a, $b]) {
            $other = (new IsAllOfModel)->forceFill(['a' => $a, 'b' => $b]);
            foreach ([['a'], ['b'], ['a', 'b'], ['b', 'a']] as $columns) {
                $state = "[$a, $b] " . implode(', ', $columns);
                $this->assertSame($model->sameWithOtherOr($other, ...$columns), $model->sameWithOther($other, ...$columns, ...['or']), $state);
                $this->assertSame($model->notSameWithOther($other, ...$columns), ! $model->sameWithOther($other, ...$columns, ...['or']), $state);
                $this->assertSame($model->notSameWithOtherOr($other, ...$columns), ! $model->sameWithOther($other, ...$columns), $state);
            }
            $this->assertSame($model->sameWithOtherOr($other), $model->sameWithOther($other, ...['or']));
        }
    }

    #[Test]
    public function it_gives_the_same_result_as_the_deprecated_and_dirty_methods(): void
    {
        $other = (new IsAllOfModel)->forceFill(['a' => 10, 'b' => 20]);
        $type = ColumnCreator::type('owner');
        $alias = Relation::getMorphAlias(IsAllOfModel::class);
        $conditions = ['a' => 10, 'b' => [2, 20]];

        foreach ([[1, 2], [10, 2], [1, 20], [10, 20]] as [$a0, $b0]) {
            foreach ([[1, 2], [10, 2], [1, 20], [10, 20], [null, ''], [' ', null]] as [$a, $b]) {
                $m = (new IsAllOfModel)->forceFill(['a' => $a0, 'b' => $b0, $type => 'x'])->syncOriginal();
                $m->a = $a;
                $m->b = $b;
                if ($a === 10) {
                    $m->{$type} = $alias;
                }
                $state = "[$a0, $b0] -> [" . var_export($a, true) . ', ' . var_export($b, true) . ']';

                $this->assertSame($m->sameWithAndDirty('a', 10), $m->isAllOf('a', 'dirty', sameWith: [10, null]), $state);
                $this->assertSame($m->sameWithAndDirty('a', '10', 'integer'), $m->isAllOf('a', 'dirty', sameWith: ['10', 'integer']), $state);
                $this->assertSame($m->notSameWithAndDirty('a', 10), $m->isAllOf('a', 'dirty', notSameWith: [10, null]), $state);
                $this->assertSame($m->sameWithOtherAndDirty($other, 'a', 'b'), $m->isAll('dirty', 'a', 'b') && $m->sameWithOther($other, 'a', 'b'), $state);
                $this->assertSame($m->notSameWithOtherAndDirty($other, 'a', 'b'), $m->isAll('dirty', 'a', 'b') && ! $m->sameWithOther($other, 'a', 'b', 'or'), $state);
                $this->assertSame($m->sameWithOtherOrAndDirty($other, 'a', 'b'), array_any(['a', 'b'], fn ($c) => $m->isAllOf($c, 'dirty', sameWith: [$other->{$c}])), $state);
                $this->assertSame($m->notSameWithOtherOrAndDirty($other, 'a', 'b'), array_any(['a', 'b'], fn ($c) => $m->isAllOf($c, 'dirty', notSameWith: [$other->{$c}])), $state);
                $this->assertSame($m->matchAndDirty($conditions), $m->isAll('dirty', array_keys($conditions)) && $m->match($conditions), $state);
                $this->assertSame($m->matchAndDirty([]), $m->isAll('dirty', []) && $m->match([]), $state);
                $this->assertSame($m->isMorphModelSameAndDirty('owner', IsAllOfModel::class), $m->isAllOf($type, 'dirty', sameWith: [$alias]), $state);
                $this->assertSame($m->isBlankAndDirty('b'), $m->isAllOf('b', 'dirty', 'blank'), $state);
                $this->assertSame($m->isFilledAndDirty('b'), $m->isAllOf('b', 'dirty', 'filled'), $state);
                $this->assertSame($m->isNullAndDirty('a'), $m->isAllOf('a', 'dirty', 'null'), $state);
                $this->assertSame($m->isNotNullAndDirty('a'), $m->isAllOf('a', 'dirty', 'not_null'), $state);
            }
        }
    }

    #[Test]
    public function it_throws_for_an_unknown_check(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->model()->isAllOf('qty', 'kompact_missing_check');
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
