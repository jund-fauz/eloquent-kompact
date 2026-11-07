<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use ArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use JundFauz\EloquentKompact\Builder\Relation;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

use function JundFauz\EloquentKompact\add_tab;
use function JundFauz\EloquentKompact\array_filter_filled;
use function JundFauz\EloquentKompact\array_key_change;
use function JundFauz\EloquentKompact\array_key_values;
use function JundFauz\EloquentKompact\array_normalize;
use function JundFauz\EloquentKompact\assign_as;
use function JundFauz\EloquentKompact\array_flat;
use function JundFauz\EloquentKompact\array_get_or;
use function JundFauz\EloquentKompact\array_pull;
use function JundFauz\EloquentKompact\array_pull_list;
use function JundFauz\EloquentKompact\array_wrap;
use function JundFauz\EloquentKompact\comment;
use function JundFauz\EloquentKompact\full_path_on_app;
use function JundFauz\EloquentKompact\higher_than;
use function JundFauz\EloquentKompact\higher_than_or_same_with;
use function JundFauz\EloquentKompact\is_all_null;
use function JundFauz\EloquentKompact\is_associative_array;
use function JundFauz\EloquentKompact\is_count_many;
use function JundFauz\EloquentKompact\is_count_one;
use function JundFauz\EloquentKompact\is_count_zero;
use function JundFauz\EloquentKompact\is_app_local_or_test;
use function JundFauz\EloquentKompact\is_natural_number;
use function JundFauz\EloquentKompact\is_whole_number;
use function JundFauz\EloquentKompact\is_zero;
use function JundFauz\EloquentKompact\is_not_array;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\lower_than;
use function JundFauz\EloquentKompact\lower_than_or_same_with;
use function JundFauz\EloquentKompact\merge_as_array;
use function JundFauz\EloquentKompact\model_compact;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\not;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\path_concat;
use function JundFauz\EloquentKompact\relation_concat;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\same_with_one_of;
use function JundFauz\EloquentKompact\schema_concat;
use function JundFauz\EloquentKompact\str_before_last;
use function JundFauz\EloquentKompact\stringify;
use function JundFauz\EloquentKompact\str_concat;
use function JundFauz\EloquentKompact\str_empty_if_false;
use function JundFauz\EloquentKompact\str_lower;
use function JundFauz\EloquentKompact\str_upper;
use function JundFauz\EloquentKompact\word_concat;
use function JundFauz\EloquentKompact\write_line;

use const JundFauz\EloquentKompact\DISTANCE_LINE;
use const JundFauz\EloquentKompact\NAMESPACE_DELIMITER;
use const JundFauz\EloquentKompact\NEW_LINE;
use const JundFauz\EloquentKompact\PATH_DELIMITER;
use const JundFauz\EloquentKompact\TAB;

class FunctionsTest extends TestCase
{
    #[Test]
    public function it_assigns_one_value_to_several_keys(): void
    {
        $this->assertSame(['created_by' => 1, 'updated_by' => 1], assign_as(1, 'created_by', 'updated_by'));
        $this->assertSame(['a' => null], assign_as(null, 'a'));
        $this->assertSame(['a' => ['x'], 'b' => ['x']], assign_as(['x'], 'a', 'b'));
    }

    #[Test]
    public function it_gives_an_empty_array_without_keys_to_assign(): void
    {
        $this->assertSame([], assign_as(1));
    }

    #[Test]
    public function it_gets_the_key_and_the_value_of_a_single_item_array(): void
    {
        $this->assertSame(['latest', 'a'], array_key_values(['latest' => 'a']));
        $this->assertSame([0, 'a'], array_key_values(['a']));
    }

    #[Test]
    public function it_gets_every_key_then_every_value_of_several_items(): void
    {
        $this->assertSame(['a', 'b', 1, 2], array_key_values(['a' => 1, 'b' => 2]));
        $this->assertSame([], array_key_values([]));
    }

    #[Test]
    public function it_normalizes_a_single_item_list_to_its_item(): void
    {
        $this->assertSame('a', array_normalize(['a']));
    }

    #[Test]
    public function it_keeps_a_list_with_another_count_when_normalizing(): void
    {
        $this->assertSame(['a', 'b'], array_normalize(['a', 'b']));
        $this->assertSame([], array_normalize([]));
    }

    public static function strConcatProvider(): iterable
    {
        yield 'nothing' => [[], ''];
        yield 'one' => [['a'], 'a'];
        yield 'many' => [['a', 'b', 'c'], 'abc'];
        yield 'with empty' => [['a', '', 'b'], 'ab'];
        yield 'with spaces' => [['a ', ' b'], 'a  b'];
        yield 'with array' => [['a', ['b', 'c'], 'd'], 'abcd'];
        yield 'with nested array' => [['a', ['b', ['c']]], 'abc'];
    }

    #[Test]
    #[DataProvider('strConcatProvider')]
    public function it_concatenates_strings(array $values, string $expected): void
    {
        $this->assertSame($expected, str_concat(...$values));
    }

    #[Test]
    public function it_joins_with_delimiters(): void
    {
        $this->assertSame('a b c', word_concat('a', ['b', 'c']));
        $this->assertSame('a:b:c', schema_concat('a', ['b', 'c']));
        $this->assertSame("a\nb\nc", write_line('a', ['b', 'c']));
        $this->assertSame('a/b/c', path_concat('a', ['b', 'c']));
        $this->assertSame('a.b.c', relation_concat('a', ['b', 'c']));
        $this->assertSame('// a', comment('a'));
    }

    #[Test]
    public function it_adds_tabs(): void
    {
        $this->assertSame('    a', add_tab('a'));
        $this->assertSame('        a', add_tab('a', 2));
        $this->assertSame(TAB . TAB . TAB . 'a', add_tab('a', 3));
        $this->assertSame('a', add_tab('a', 0));
        $this->assertSame('a', add_tab('a', -1));
    }

    #[Test]
    public function it_concatenates_namespaces(): void
    {
        $this->assertSame('App\Models\User', namespace_concat('App', 'Models', 'User'));
        $this->assertSame('App\Models\User', namespace_concat('\App\\', ['Models\\', '\User']));
        $this->assertSame('App', namespace_concat('App'));
    }

    #[Test]
    public function it_builds_full_paths_on_the_app_directory(): void
    {
        $this->assertSame(app_path('Models'), full_path_on_app('App\Models'));
        $this->assertSame(app_path('Models'), full_path_on_app('App/Models/'));
        $this->assertSame(app_path('Database/Models'), full_path_on_app('app\Database\Models'));
        $this->assertSame(app_path('Application'), full_path_on_app('Application'));
    }

    #[Test]
    public function it_flattens_arrays_in_place(): void
    {
        $array = ['a', ['b', ['c']]];

        array_flat($array);

        $this->assertSame(['a', 'b', 'c'], $array);
    }

    #[Test]
    public function it_pulls_one_key(): void
    {
        $array = ['name' => 'Ana', 'email' => 'ana@example.com'];

        $this->assertSame('Ana', array_pull($array, 'name'));
        $this->assertSame(['email' => 'ana@example.com'], $array);
    }

    #[Test]
    public function it_pulls_several_keys(): void
    {
        $array = ['name' => 'Ana', 'email' => 'ana@example.com', 'role' => 'admin'];

        $this->assertSame(['name' => 'Ana', 'role' => 'admin'], array_pull($array, 'name', 'role'));
        $this->assertSame(['email' => 'ana@example.com'], $array);
    }

    #[Test]
    public function it_pulls_missing_keys_as_null(): void
    {
        $array = ['name' => 'Ana'];

        $this->assertNull(array_pull($array, 'email'));
        $this->assertSame(['name' => 'Ana', 'email' => null], array_pull($array, 'name', 'email'));
        $this->assertSame([], $array);
    }

    #[Test]
    public function it_pulls_several_keys_as_a_list(): void
    {
        $array = ['name' => 'Ana', 'email' => 'ana@example.com', 'role' => 'admin'];

        $this->assertSame(['admin', 'Ana'], array_pull_list($array, 'role', 'name'));
        $this->assertSame(['email' => 'ana@example.com'], $array);
    }

    #[Test]
    public function it_pulls_a_single_key_as_its_value(): void
    {
        $array = ['name' => 'Ana', 'tags' => ['a' => 1]];

        $this->assertSame('Ana', array_pull_list($array, 'name'));
        $this->assertSame(['a' => 1], array_pull_list($array, 'tags'));
        $this->assertSame([], $array);
    }

    #[Test]
    public function it_pulls_missing_keys_as_null_in_the_list(): void
    {
        $array = ['name' => 'Ana'];

        $this->assertNull(array_pull_list($array, 'email'));
        $this->assertSame(['Ana', null], array_pull_list($array, 'name', 'email'));
        $this->assertSame([], array_pull_list($array));
    }

    #[Test]
    public function it_merges_values_as_a_flat_list(): void
    {
        $this->assertSame(['a', 'b', 'c', 'd', null], merge_as_array('a', ['b', ['c']], ['k' => 'd'], null, []));
        $this->assertSame([1, 2], merge_as_array(collect([1, 2])));
        $this->assertSame([1, 2, 'b'], merge_as_array(collect([1, 2]), ['k' => 'b']));
        $this->assertSame([], merge_as_array());
    }

    #[Test]
    public function it_merges_associative_arrays_with_their_keys(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], merge_as_array(['a' => 1], ['b' => 2]));
        $this->assertSame(['a' => 1], merge_as_array(['a' => 1]));
        $this->assertSame(['a' => 9, 'b' => 2], merge_as_array(['a' => 1, 'b' => 2], ['a' => 9]));
        $this->assertSame(['a' => ['x' => [1, 2]], 'b' => [3]], merge_as_array(['a' => ['x' => [1, 2]]], ['b' => [3]]));
        $this->assertSame(['a' => null, 'b' => 2], merge_as_array(['a' => null], ['b' => 2]));
    }

    #[Test]
    public function it_merges_associative_collections_with_their_keys(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], merge_as_array(collect(['a' => 1]), ['b' => 2]));
        $this->assertSame(['a' => 1, 'b' => 2], merge_as_array(new SupportCollection(['a' => 1]), new SupportCollection(['b' => 2])));
    }

    #[Test]
    public function it_ignores_blank_values_when_merging_associative_arrays(): void
    {
        $this->assertSame(['a' => 1], merge_as_array([], ['a' => 1], []));
        $this->assertSame(['a' => 1], merge_as_array(['a' => 1], null, '', '   ', collect()));
    }

    #[Test]
    public function it_drops_the_keys_when_an_associative_array_is_mixed_with_other_values(): void
    {
        $this->assertSame([1, 'x'], merge_as_array(['a' => 1], 'x'));
        $this->assertSame([1, 5, 6], merge_as_array(['a' => 1], [5, 6]));
        $this->assertSame([1, false], merge_as_array(['a' => 1], false));
        $this->assertSame([1, 0], merge_as_array(['a' => 1], 0));
    }

    #[Test]
    public function it_merges_named_arguments_without_an_error(): void
    {
        $this->assertSame([1, 2], merge_as_array(a: 1, b: 2));
        $this->assertSame(['k' => 1, 'j' => 2], merge_as_array(a: ['k' => 1], b: ['j' => 2]));
    }

    #[Test]
    public function it_renumbers_numeric_keys_when_merging(): void
    {
        $this->assertSame(['x', 'y'], merge_as_array([5 => 'x'], [7 => 'y']));
    }

    #[Test]
    public function it_keeps_array_key_values_flat_when_the_value_is_associative(): void
    {
        $this->assertSame(['k', 1], array_key_values(['k' => ['a' => 1]]));
    }

    #[Test]
    public function it_checks_whether_a_value_is_an_associative_array(): void
    {
        $this->assertTrue(is_associative_array(['a' => 1]));
        $this->assertTrue(is_associative_array([5 => 'x']));
        $this->assertFalse(is_associative_array([]));
        $this->assertFalse(is_associative_array([1, 2]));
        $this->assertFalse(is_associative_array('a'));
        $this->assertFalse(is_associative_array(null));
        $this->assertFalse(is_associative_array(collect(['a' => 1])));
    }

    #[Test]
    public function it_wraps_values_in_arrays(): void
    {
        $this->assertSame(['a'], array_wrap('a'));
        $this->assertSame(['a', 'b'], array_wrap(['a', 'b']));
        $this->assertSame([], array_wrap(null));
    }

    #[Test]
    public function it_gets_the_first_non_null_value(): void
    {
        $array = ['nickname' => null, 'name' => 'Ana', 'empty' => '', 'zero' => 0, 'user' => ['name' => 'Budi']];

        $this->assertSame('Ana', array_get_or($array, 'nickname', 'name'));
        $this->assertSame('', array_get_or($array, 'empty', 'name'));
        $this->assertSame(0, array_get_or($array, 'zero', 'name'));
        $this->assertSame('Ana', array_get_or($array, 'missing', 'name'));
        $this->assertSame('Budi', array_get_or($array, 'user.name'));
        $this->assertNull(array_get_or($array, 'nickname', 'missing'));
        $this->assertNull(array_get_or($array));
    }

    #[Test]
    public function it_filters_out_blank_values(): void
    {
        $this->assertSame(
            [0 => 'a', 4 => 0, 5 => '0', 6 => false, 'k' => 'v'],
            array_filter_filled(['a', '', '  ', null, 0, '0', false, [], 'k' => 'v', 'e' => ''])
        );
        $this->assertSame([[0]], array_filter_filled([[0], collect()]));
        $this->assertSame([], array_filter_filled([]));
    }

    #[Test]
    public function it_changes_an_array_key(): void
    {
        $data = ['a' => 1, 'b' => null];

        array_key_change($data, 'a', 'c');
        array_key_change($data, 'b', 'd');

        $this->assertSame(['c' => 1, 'd' => null], $data);
    }

    #[Test]
    public function it_keeps_the_array_when_the_key_does_not_change(): void
    {
        $data = ['a' => 1, 'b' => 2];

        array_key_change($data, 'a', 'a');

        $this->assertSame(['a' => 1, 'b' => 2], $data);
    }

    #[Test]
    public function it_replaces_the_value_of_an_existing_key(): void
    {
        $data = ['a' => 1, 'b' => 2];

        array_key_change($data, 'a', 'b');

        $this->assertSame(['b' => 1], $data);
    }

    #[Test]
    public function it_fails_when_the_old_key_is_missing(): void
    {
        $data = ['a' => 1];

        $this->expectException(\ErrorException::class);

        array_key_change($data, 'missing', 'b');
    }

    #[Test]
    public function it_checks_values(): void
    {
        $this->assertTrue(is_not_null(''));
        $this->assertFalse(is_not_null(null));
        $this->assertTrue(is_all_null(null, [null, [null]]));
        $this->assertFalse(is_all_null(null, [0]));
        $this->assertTrue(is_not_array('a'));
        $this->assertFalse(is_not_array([]));
        $this->assertTrue(not(false));
        $this->assertFalse(not(true));
    }

    #[Test]
    public function it_compares_values_strictly(): void
    {
        $object = new stdClass;

        $this->assertTrue(same_with($object, $object));
        $this->assertFalse(same_with(1, '1'));
        $this->assertFalse(same_with(new stdClass, new stdClass));
        $this->assertTrue(not_same_with(1, '1'));
        $this->assertFalse(not_same_with(null, null));
    }

    #[Test]
    public function it_compares_with_one_of_the_values_strictly(): void
    {
        $object = new stdClass;

        $this->assertTrue(same_with_one_of('b', 'a', 'b'));
        $this->assertTrue(same_with_one_of($object, new stdClass, $object));
        $this->assertTrue(same_with_one_of(null, 0, null));
        $this->assertFalse(same_with_one_of(1, '1'));
        $this->assertFalse(same_with_one_of(null, false, '', 0));
        $this->assertFalse(same_with_one_of('1e1', '10'));
        $this->assertFalse(same_with_one_of(true, 'x'));
        $this->assertFalse(same_with_one_of(new stdClass, new stdClass));
        $this->assertFalse(same_with_one_of('a'));
    }

    #[Test]
    public function it_compares_numbers(): void
    {
        $this->assertTrue(higher_than(2, 1));
        $this->assertTrue(higher_than(1.5, 1));
        $this->assertTrue(higher_than('2.50', '2.00'));
        $this->assertTrue(higher_than('10', '9'));
        $this->assertFalse(higher_than(1, 1));
        $this->assertFalse(higher_than(0.2, 0.9));

        $this->assertTrue(lower_than(1, 2));
        $this->assertTrue(lower_than(0.2, 0.9));
        $this->assertTrue(lower_than('9', '10'));
        $this->assertTrue(lower_than(-1, '0.5'));
        $this->assertFalse(lower_than(1, 1));
        $this->assertFalse(lower_than('2.50', 2));
    }

    #[Test]
    public function it_compares_numbers_or_same(): void
    {
        $this->assertTrue(higher_than_or_same_with(2, 1));
        $this->assertTrue(higher_than_or_same_with(1, 1));
        $this->assertTrue(higher_than_or_same_with('2.50', 2.5));
        $this->assertTrue(higher_than_or_same_with('10', '9'));
        $this->assertFalse(higher_than_or_same_with(0.2, 0.9));

        $this->assertTrue(lower_than_or_same_with(1, 2));
        $this->assertTrue(lower_than_or_same_with(1, 1));
        $this->assertTrue(lower_than_or_same_with('2.50', 2.5));
        $this->assertTrue(lower_than_or_same_with('9', '10'));
        $this->assertFalse(lower_than_or_same_with(0.9, 0.2));
    }

    #[Test]
    public function it_checks_zero(): void
    {
        $this->assertTrue(is_zero(0));
        $this->assertTrue(is_zero(0.0));
        $this->assertTrue(is_zero(-0.0));
        $this->assertFalse(is_zero(1));
        $this->assertFalse(is_zero(0.1));
        $this->assertFalse(is_zero(-1));
    }

    #[Test]
    public function it_checks_the_count(): void
    {
        $this->assertTrue(is_count_zero([]));
        $this->assertTrue(is_count_zero(new ArrayObject));
        $this->assertFalse(is_count_zero([null]));

        $this->assertTrue(is_count_one([null]));
        $this->assertTrue(is_count_one(new ArrayObject(['a' => 1])));
        $this->assertFalse(is_count_one([]));
        $this->assertFalse(is_count_one([1, 2]));

        $this->assertTrue(is_count_many([1, 2]));
        $this->assertTrue(is_count_many(new ArrayObject([1, 2, 3])));
        $this->assertFalse(is_count_many([1]));
        $this->assertFalse(is_count_many([]));
    }

    #[Test]
    public function it_stringifies_arrays(): void
    {
        $this->assertSame(
            ['user.name', 'k' => ['posts', '1'], '1.5', '1', '', ''],
            stringify([Relation::arrange('name')->relation('user'), 'k' => [Relation::arrange()->relations('posts'), 1], 1.5, true, false, null])
        );
        $this->assertSame([], stringify([]));
    }

    #[Test]
    public function it_wraps_laravel_str_methods(): void
    {
        $this->assertSame('App\Models', str_before_last('App\Models\User', '\\'));
        $this->assertSame('äöü', str_lower('ÄÖÜ'));
        $this->assertSame('ÄÖÜ', str_upper('äöü'));
    }

    #[Test]
    public function it_checks_a_local_or_test_environment(): void
    {
        $this->assertTrue(is_app_local_or_test());

        $this->app['env'] = 'local';
        $this->assertTrue(is_app_local_or_test());

        foreach (['production', 'staging'] as $environment) {
            $this->app['env'] = $environment;
            $this->assertFalse(is_app_local_or_test(), $environment);
        }
    }

    #[Test]
    public function it_checks_natural_and_whole_numbers(): void
    {
        $this->assertTrue(is_natural_number(1));
        $this->assertTrue(is_natural_number(PHP_INT_MAX));
        $this->assertFalse(is_natural_number(0));
        $this->assertFalse(is_natural_number(-1));

        $this->assertTrue(is_whole_number(0));
        $this->assertTrue(is_whole_number(1));
        $this->assertFalse(is_whole_number(-1));
        $this->assertFalse(is_whole_number(PHP_INT_MIN));
    }

    #[Test]
    public function str_empty_if_false_keeps_the_value_only_when_the_condition_is_truthy(): void
    {
        $this->assertSame('a', str_empty_if_false(true, 'a'));
        $this->assertSame('a', str_empty_if_false('x', 'a'));
        $this->assertSame('a', str_empty_if_false([1], 'a'));

        foreach ([false, null, 0, '', '0', []] as $condition) {
            $this->assertSame('', str_empty_if_false($condition, 'a'));
        }
    }

    private static function model(array $attributes): Model
    {
        $model = new class extends Model {
            protected $guarded = [];

            protected $casts = ['active' => 'boolean', 'tags' => 'array'];
        };

        return $model->fill($attributes);
    }

    #[Test]
    public function it_compacts_model_attributes(): void
    {
        $model = self::model(['name' => 'a', 'email' => 'a@b.c', 'active' => 1, 'tags' => ['x']]);

        $this->assertSame(['name' => 'a', 'active' => true], model_compact($model, 'name', 'active'));
        $this->assertSame(['tags' => ['x']], model_compact($model, 'tags'));
    }

    #[Test]
    public function it_keeps_the_given_column_order(): void
    {
        $model = self::model(['name' => 'a', 'email' => 'a@b.c']);

        $this->assertSame(['email', 'name'], array_keys(model_compact($model, 'email', 'name')));
    }

    #[Test]
    public function it_returns_null_for_missing_attributes(): void
    {
        $this->assertSame(['missing' => null], model_compact(self::model([]), 'missing'));
    }

    #[Test]
    public function it_returns_an_empty_array_without_columns(): void
    {
        $this->assertSame([], model_compact(self::model(['name' => 'a'])));
    }

    #[Test]
    public function it_converts_loaded_relations_to_arrays(): void
    {
        $model = self::model(['name' => 'a']);
        $model->setRelation('owner', self::model(['name' => 'b']));

        $this->assertSame(['owner' => ['name' => 'b']], model_compact($model, 'owner'));
    }

    #[Test]
    public function it_defines_the_constants(): void
    {
        $this->assertSame("\n", NEW_LINE);
        $this->assertSame("\n\n", DISTANCE_LINE);
        $this->assertSame('\\', NAMESPACE_DELIMITER);
        $this->assertSame('/', PATH_DELIMITER);
        $this->assertSame('    ', TAB);
    }

    #[Test]
    public function it_is_only_available_in_the_package_namespace(): void
    {
        $this->assertTrue(function_exists('JundFauz\EloquentKompact\str_concat'));
        $this->assertFalse(function_exists('str_concat'));
    }
}
