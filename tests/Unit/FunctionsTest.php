<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use JundFauz\EloquentKompact\Builder\Relation;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

use function JundFauz\EloquentKompact\add_tab;
use function JundFauz\EloquentKompact\array_flat;
use function JundFauz\EloquentKompact\array_pull;
use function JundFauz\EloquentKompact\comment;
use function JundFauz\EloquentKompact\full_path_on_app;
use function JundFauz\EloquentKompact\is_all_null;
use function JundFauz\EloquentKompact\is_not_array;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\model_compact;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\not;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\path_concat;
use function JundFauz\EloquentKompact\relation_concat;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\schema_concat;
use function JundFauz\EloquentKompact\str_before_last;
use function JundFauz\EloquentKompact\stringify;
use function JundFauz\EloquentKompact\str_concat;
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
