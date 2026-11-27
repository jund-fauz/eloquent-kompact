<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use ArrayObject;
use BadMethodCallException;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\File;
use JundFauz\EloquentKompact\Array as Arr;
use JundFauz\EloquentKompact\Builder\Relation;
use JundFauz\EloquentKompact\Cast\UuidCast;
use JundFauz\EloquentKompact\String as Str;
use JundFauz\EloquentKompact\Builder\Str as KompactStr;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use stdClass;
use function JundFauz\EloquentKompact\add_extension;
use function JundFauz\EloquentKompact\add_php_extension;
use function JundFauz\EloquentKompact\add_tab;
use function JundFauz\EloquentKompact\build_class;
use function JundFauz\EloquentKompact\build_class_docblock;
use function JundFauz\EloquentKompact\cast_as;
use function JundFauz\EloquentKompact\comment;
use function JundFauz\EloquentKompact\comment_doc;
use function JundFauz\EloquentKompact\create_php_content;
use function JundFauz\EloquentKompact\ensure_directory_exists;
use function JundFauz\EloquentKompact\full_path_on_app;
use function JundFauz\EloquentKompact\if_not_null;
use function JundFauz\EloquentKompact\null_if;
use function JundFauz\EloquentKompact\null_if_not;
use function JundFauz\EloquentKompact\nullIf;
use function JundFauz\EloquentKompact\is;
use function JundFauz\EloquentKompact\is_all;
use function JundFauz\EloquentKompact\is_all_type_of;
use function JundFauz\EloquentKompact\is_any;
use function JundFauz\EloquentKompact\is_any_not_type_of;
use function JundFauz\EloquentKompact\is_all_of;
use function JundFauz\EloquentKompact\is_any_of;
use function JundFauz\EloquentKompact\is_app_local_or_test;
use function JundFauz\EloquentKompact\is_file_exist;
use function JundFauz\EloquentKompact\is_not;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\object_compact;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\namespace_trim;
use function JundFauz\EloquentKompact\not;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\path_concat;
use function JundFauz\EloquentKompact\relation_concat;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\same_with_one_of;
use function JundFauz\EloquentKompact\execute_what_function;
use function JundFauz\EloquentKompact\concat_by;
use function JundFauz\EloquentKompact\schema_concat;
use function JundFauz\EloquentKompact\syntax;
use function JundFauz\EloquentKompact\word_concat;
use function JundFauz\EloquentKompact\write_file;
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
        $this->assertSame(['created_by' => 1, 'updated_by' => 1], Arr\assign_as(1, 'created_by', 'updated_by'));
        $this->assertSame(['a' => null], Arr\assign_as(null, 'a'));
        $this->assertSame(['a' => ['x'], 'b' => ['x']], Arr\assign_as(['x'], 'a', 'b'));
    }

    #[Test]
    public function it_gives_an_empty_array_without_keys_to_assign(): void
    {
        $this->assertSame([], Arr\assign_as(1));
    }

    #[Test]
    public function it_gets_the_key_and_the_value_of_a_single_item_array(): void
    {
        $this->assertSame(['latest', 'a'], Arr\key_values(['latest' => 'a']));
        $this->assertSame([0, 'a'], Arr\key_values(['a']));
    }

    #[Test]
    public function it_gets_every_key_then_every_value_of_several_items(): void
    {
        $this->assertSame(['a', 'b', 1, 2], Arr\key_values(['a' => 1, 'b' => 2]));
        $this->assertSame([], Arr\key_values([]));
    }

    #[Test]
    public function it_normalizes_a_single_item_list_to_its_item(): void
    {
        $this->assertSame('a', Arr\normalize(['a']));
    }

    #[Test]
    public function it_keeps_a_list_with_another_count_when_normalizing(): void
    {
        $this->assertSame(['a', 'b'], Arr\normalize(['a', 'b']));
        $this->assertSame([], Arr\normalize([]));
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
        $this->assertSame($expected, Str\concat(...$values));
    }

    #[Test]
    public function it_joins_with_delimiters(): void
    {
        $this->assertSame('a b c', word_concat('a', ['b', 'c']));
        $this->assertSame('posts.user', concat_by(\JundFauz\EloquentKompact\relation, 'posts', ['user']));
        $this->assertSame('a_b_c', concat_by(\JundFauz\EloquentKompact\snake, 'a', ['b', ['c']]));
        $this->assertSame('a-b', concat_by('-', 'a', 'b'));
        $this->assertSame('', concat_by('-'));
        $this->assertSame('a:b:c', schema_concat('a', ['b', 'c']));
        $this->assertSame("a\nb\nc", write_line('a', ['b', 'c']));
        $this->assertSame('a/b/c', path_concat('a', ['b', 'c']));
        $this->assertSame('a.b.c', relation_concat('a', ['b', 'c']));
        $this->assertSame('// a', comment('a'));
        $this->assertSame(' * a', comment_doc('a'));
        $this->assertSame('use A;', syntax('use A'));
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
    public function it_trims_a_namespace(): void
    {
        $this->assertSame('App\Models', namespace_trim('\App\Models\\'));
        $this->assertSame('App/Models', namespace_trim('/App/Models/'));
        $this->assertSame('App', namespace_trim('App'));
    }

    #[Test]
    public function it_adds_a_file_extension(): void
    {
        $this->assertSame('config.json', add_extension('config', 'json'));
        $this->assertSame('Money.php', add_php_extension('Money'));
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
    public function it_writes_and_finds_a_file(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kompact-functions-' . uniqid() . '/nested';

        try {
            ensure_directory_exists($directory);
            $this->assertDirectoryExists($directory);
            $this->assertFalse(is_file_exist($directory, 'a.txt'));

            write_file($directory, 'a.txt', 'content');

            $this->assertTrue(is_file_exist($directory, 'a.txt'));
            $this->assertSame('content', File::get($directory . '/a.txt'));
        } finally {
            File::deleteDirectory(dirname($directory));
        }
    }

    #[Test]
    public function it_builds_a_class(): void
    {
        $this->assertSame('class X {}', build_class('X'));
        $this->assertSame('class X extends P {}', build_class('X', parent: 'P'));
        $this->assertSame("class X extends P\n{\n}", build_class('X', "\n{\n}", 'P'));
        $this->assertSame("/**\n * a\n */\nclass X {}", build_class('X', docblock: "/**\n * a\n */\n"));
    }

    #[Test]
    public function it_builds_a_class_docblock(): void
    {
        $docblock = build_class_docblock(['a', 'b' => 'int'], 'string', true, 'int $x', 'Generated');

        $this->assertStringStartsWith("/**\n * Generated\n", $docblock);
        $this->assertStringContainsString("\n * @method static string a(int \$x)\n", $docblock);
        $this->assertStringContainsString("\n * @method static int b(int \$x)\n", $docblock);
        $this->assertStringEndsWith("\n */\n", $docblock);
        $this->assertStringContainsString(' * @method mixed a', build_class_docblock(['a']));
    }

    #[Test]
    public function it_creates_the_content_of_a_php_file(): void
    {
        $this->assertSame(
            "<?php\n\nnamespace App\Logic;\n\nuse A\B;\nuse C\D as E;\n\nclass X {}",
            create_php_content('App\Logic', ['A\B', 'C\D' => 'E'], 'class X {}')
        );
        $this->assertSame("<?php\n\nnamespace App;\n\nuse A\B;\n\nclass X {}", create_php_content('App', 'A\B', 'class X {}'));
    }

    #[Test]
    public function it_flattens_arrays_in_place(): void
    {
        $array = ['a', ['b', ['c']]];

        Arr\flat($array);

        $this->assertSame(['a', 'b', 'c'], $array);
    }

    #[Test]
    public function it_maps_with_the_keys(): void
    {
        $this->assertSame(['a=1', 'b=2'], Arr\map(fn ($value, $key) => "$key=$value", ['a' => 1, 'b' => 2]));
        $this->assertSame([2, 4], Arr\map(fn ($value) => $value * 2, [1, 2]));
        $this->assertSame([], Arr\map(fn ($value) => $value, []));
    }

    #[Test]
    public function it_filters_an_array_keeping_its_keys(): void
    {
        $this->assertSame(['a' => 1, 'c' => 0], Arr\filter(['a' => 1, 'b' => null, 'c' => 0], is_not_null(...)));
        $this->assertSame(
            [0 => 'x', 'keep' => null],
            Arr\filter([0 => 'x', 1 => null, 'keep' => null], fn ($value, $key) => is_string($key) || is_not_null($value)),
        );
        $this->assertSame([0 => 'a', 'k' => 'v'], Arr\filter([0 => 'a', 1 => '', 2 => 0, 3 => null, 'k' => 'v']));
        $this->assertSame([], Arr\filter([], is_not_null(...)));
    }

    #[Test]
    public function it_pulls_one_key(): void
    {
        $array = ['name' => 'Ana', 'email' => 'ana@example.com'];

        $this->assertSame('Ana', Arr\pull($array, 'name'));
        $this->assertSame(['email' => 'ana@example.com'], $array);
    }

    #[Test]
    public function it_pulls_several_keys(): void
    {
        $array = ['name' => 'Ana', 'email' => 'ana@example.com', 'role' => 'admin'];

        $this->assertSame(['name' => 'Ana', 'role' => 'admin'], Arr\pull($array, 'name', 'role'));
        $this->assertSame(['email' => 'ana@example.com'], $array);
    }

    #[Test]
    public function it_pulls_missing_keys_as_null(): void
    {
        $array = ['name' => 'Ana'];

        $this->assertNull(Arr\pull($array, 'email'));
        $this->assertSame(['name' => 'Ana', 'email' => null], Arr\pull($array, 'name', 'email'));
        $this->assertSame([], $array);
    }

    #[Test]
    public function it_pulls_with_dot_notation(): void
    {
        $array = ['user' => ['name' => 'Ana', 'email' => 'ana@example.com']];

        $this->assertSame('Ana', Arr\pull($array, 'user.name'));
        $this->assertSame(['user' => ['email' => 'ana@example.com']], $array);
    }

    #[Test]
    public function it_pulls_several_keys_as_a_list(): void
    {
        $array = ['name' => 'Ana', 'email' => 'ana@example.com', 'role' => 'admin'];

        $this->assertSame(['admin', 'Ana'], Arr\pull_list($array, 'role', 'name'));
        $this->assertSame(['email' => 'ana@example.com'], $array);
    }

    #[Test]
    public function it_pulls_a_single_key_as_its_value(): void
    {
        $array = ['name' => 'Ana', 'tags' => ['a' => 1]];

        $this->assertSame('Ana', Arr\pull_list($array, 'name'));
        $this->assertSame(['a' => 1], Arr\pull_list($array, 'tags'));
        $this->assertSame([], $array);
    }

    #[Test]
    public function it_pulls_missing_keys_as_null_in_the_list(): void
    {
        $array = ['name' => 'Ana'];

        $this->assertNull(Arr\pull_list($array, 'email'));
        $this->assertSame(['Ana', null], Arr\pull_list($array, 'name', 'email'));
        $this->assertSame([], Arr\pull_list($array));
    }

    #[Test]
    public function it_merges_values_as_a_flat_list(): void
    {
        $this->assertSame(['a', 'b', 'c', 'd', null], Arr\merge_as_array('a', ['b', ['c']], ['k' => 'd'], null, []));
        $this->assertSame([1, 2], Arr\merge_as_array(collect([1, 2])));
        $this->assertSame([1, 2, 'b'], Arr\merge_as_array(collect([1, 2]), ['k' => 'b']));
        $this->assertSame([], Arr\merge_as_array());
    }

    #[Test]
    public function it_merges_associative_arrays_with_their_keys(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], Arr\merge_as_array(['a' => 1], ['b' => 2]));
        $this->assertSame(['a' => 1], Arr\merge_as_array(['a' => 1]));
        $this->assertSame(['a' => 9, 'b' => 2], Arr\merge_as_array(['a' => 1, 'b' => 2], ['a' => 9]));
        $this->assertSame(['a' => ['x' => [1, 2]], 'b' => [3]], Arr\merge_as_array(['a' => ['x' => [1, 2]]], ['b' => [3]]));
        $this->assertSame(['a' => null, 'b' => 2], Arr\merge_as_array(['a' => null], ['b' => 2]));
    }

    #[Test]
    public function it_merges_associative_collections_with_their_keys(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], Arr\merge_as_array(collect(['a' => 1]), ['b' => 2]));
        $this->assertSame(['a' => 1, 'b' => 2], Arr\merge_as_array(new SupportCollection(['a' => 1]), new SupportCollection(['b' => 2])));
    }

    #[Test]
    public function it_ignores_blank_values_when_merging_associative_arrays(): void
    {
        $this->assertSame(['a' => 1], Arr\merge_as_array([], ['a' => 1], []));
        $this->assertSame(['a' => 1], Arr\merge_as_array(['a' => 1], null, '', '   ', collect()));
    }

    #[Test]
    public function it_drops_the_keys_when_an_associative_array_is_mixed_with_other_values(): void
    {
        $this->assertSame([1, 'x'], Arr\merge_as_array(['a' => 1], 'x'));
        $this->assertSame([1, 5, 6], Arr\merge_as_array(['a' => 1], [5, 6]));
        $this->assertSame([1, false], Arr\merge_as_array(['a' => 1], false));
        $this->assertSame([1, 0], Arr\merge_as_array(['a' => 1], 0));
    }

//    Will be tested after change in merge_as_array
//    #[Test]
//    public function it_merges_named_arguments_as_associative_arrays(): void
//    {
//        $this->assertSame(['a' => 1, 'b' => 2], Arr\merge_as_array(a: 1, b: 2));
//        $this->assertSame(['a' => 9, 'b' => 2, 'c' => 3], Arr\merge_as_array(['a' => 1, 'b' => 2], a: 9, c: 3));
//        $this->assertSame(['a' => 1, 'b' => 2], Arr\merge_as_array(collect(['a' => 1]), b: 2));
//        $this->assertSame(['a' => ['k' => 1], 'b' => [2, 3]], Arr\merge_as_array(a: ['k' => 1], b: [2, 3]));
//        $this->assertSame(['a' => null, 'b' => '', 'c' => []], Arr\merge_as_array(a: null, b: '', c: []));
//        $this->assertSame(['a' => 1], Arr\merge_as_array([], null, '', a: 1));
//        $this->assertSame(['x', 1], Arr\merge_as_array('x', a: 1));
//        $this->assertSame([5, 6, 1], Arr\merge_as_array([5, 6], a: 1));
//    }

    #[Test]
    public function it_renumbers_numeric_keys_when_merging(): void
    {
        $this->assertSame(['x', 'y'], Arr\merge_as_array([5 => 'x'], [7 => 'y']));
    }

    #[Test]
    public function it_keeps_array_key_values_flat_when_the_value_is_associative(): void
    {
        $this->assertSame(['k', 1], Arr\key_values(['k' => ['a' => 1]]));
    }

    #[Test]
    public function it_checks_whether_a_value_is_an_associative_array(): void
    {
        $this->assertTrue(Arr\is_associative(['a' => 1]));
        $this->assertTrue(Arr\is_associative([5 => 'x']));
        $this->assertFalse(Arr\is_associative([]));
        $this->assertFalse(Arr\is_associative([1, 2]));
        $this->assertFalse(Arr\is_associative('a'));
        $this->assertFalse(Arr\is_associative(null));
        $this->assertFalse(Arr\is_associative(collect(['a' => 1])));
    }

    #[Test]
    public function it_wraps_values_in_arrays(): void
    {
        $this->assertSame(['a'], Arr\wrap('a'));
        $this->assertSame(['a', 'b'], Arr\wrap(['a', 'b']));
        $this->assertSame([], Arr\wrap(null));
    }

    #[Test]
    public function it_casts_values_to_arrays(): void
    {
        $this->assertSame(['a', 'b'], Arr\cast(collect(['a', 'b'])));
        $this->assertSame(['a'], Arr\cast('a'));
        $this->assertSame([], Arr\cast(null));
        $this->assertSame(['a,b'], Arr\cast('a,b'));
        $this->assertSame(['a', 'b'], Arr\cast('a,b', ','));
        $this->assertSame(['a', 'b'], Arr\cast('a|b', '|'));
        $this->assertSame(['a', 'b'], Arr\cast(str('a,b'), ','));
        $this->assertSame(['a,b'], Arr\cast(str('a,b')));
    }

    #[Test]
    public function it_finds_a_needle_in_a_string_haystack(): void
    {
        $this->assertTrue(Arr\in('max', 'min,max'));
        $this->assertFalse(Arr\in('mi', 'min,max'));
        $this->assertTrue(Arr\in('max', str('min,max')));
        $this->assertTrue(Arr\in('max', 'min|max', delimiterIfString: '|'));
        $this->assertFalse(Arr\in('max', 'min,max', delimiterIfString: '|'));
        $this->assertTrue(Arr\in('b', collect(['a', 'b'])));
        $this->assertFalse(Arr\in(1, ['1'], true));
    }

    #[Test]
    public function it_gets_the_first_non_null_value(): void
    {
        $array = ['nickname' => null, 'name' => 'Ana', 'empty' => '', 'zero' => 0, 'user' => ['name' => 'Budi']];

        $this->assertSame('Ana', Arr\get_or($array, 'nickname', 'name'));
        $this->assertSame('', Arr\get_or($array, 'empty', 'name'));
        $this->assertSame(0, Arr\get_or($array, 'zero', 'name'));
        $this->assertSame('Ana', Arr\get_or($array, 'missing', 'name'));
        $this->assertSame('Budi', Arr\get_or($array, 'user.name'));
        $this->assertNull(Arr\get_or($array, 'nickname', 'missing'));
        $this->assertNull(Arr\get_or($array));
    }

    #[Test]
    public function it_filters_out_blank_values(): void
    {
        $this->assertSame(
            [0 => 'a', 4 => 0, 5 => '0', 6 => false, 'k' => 'v'],
            Arr\filter_filled(['a', '', '  ', null, 0, '0', false, [], 'k' => 'v', 'e' => ''])
        );
        $this->assertSame([[0]], Arr\filter_filled([[0], collect()]));
        $this->assertSame([], Arr\filter_filled([]));
    }

    #[Test]
    public function it_changes_an_array_key(): void
    {
        $data = ['a' => 1, 'b' => null];

        Arr\key_change($data, 'a', 'c');
        Arr\key_change($data, 'b', 'd');

        $this->assertSame(['c' => 1, 'd' => null], $data);
    }

    #[Test]
    public function it_keeps_the_array_when_the_key_does_not_change(): void
    {
        $data = ['a' => 1, 'b' => 2];

        Arr\key_change($data, 'a', 'a');

        $this->assertSame(['a' => 1, 'b' => 2], $data);
    }

    #[Test]
    public function it_replaces_the_value_of_an_existing_key(): void
    {
        $data = ['a' => 1, 'b' => 2];

        Arr\key_change($data, 'a', 'b');

        $this->assertSame(['b' => 1], $data);
    }

    #[Test]
    public function it_fails_when_the_old_key_is_missing(): void
    {
        $data = ['a' => 1];

        $this->expectException(\ErrorException::class);

        Arr\key_change($data, 'missing', 'b');
    }

    #[Test]
    public function it_checks_values(): void
    {
        $this->assertTrue(is_not_null(''));
        $this->assertFalse(is_not_null(null));
        $this->assertTrue(Arr\is_array_or_collection([]));
        $this->assertTrue(Arr\is_array_or_collection(new SupportCollection));
        $this->assertFalse(Arr\is_array_or_collection(new ArrayObject));
        $this->assertTrue(not(false));
        $this->assertFalse(not(true));
    }

    #[Test]
    public function it_builds_the_name_of_an_is_function(): void
    {
        $this->assertSame('is_int', is('int'));
        $this->assertSame('is_list', is('is_list'));
        $this->assertTrue(execute_what_function('is_list', [1, 2]));
        $this->assertSame('is_not_null', is('not_null'));
    }

    #[Test]
    public function it_checks_every_value(): void
    {
        $this->assertTrue(is_all('null', null, [null, [null]]));
        $this->assertFalse(is_all('null', null, [0]));
        $this->assertTrue(is_all('numeric', 1, '2.5', 1e3));
        $this->assertFalse(is_all('numeric', 1, 'abc'));
        $this->assertTrue(is_all('not_null', 1, ''));
        $this->assertTrue(is_all('null'));
    }

    #[Test]
    public function it_checks_any_value(): void
    {
        $this->assertTrue(is_any('null', 1, null));
        $this->assertFalse(is_any('null', 1, 0, ''));
        $this->assertTrue(is_any('numeric', 'abc', '2'));
        $this->assertFalse(is_any('numeric', 'abc', null));
        $this->assertFalse(is_any('null'));
    }

    #[Test]
    public function it_checks_every_value_and_any_value_negated(): void
    {
        $this->assertTrue(is_all('not_null', 0, ['', [false]]));
        $this->assertFalse(is_all('not_null', 0, [null]));
        $this->assertTrue(is_all('not_numeric', 'abc', null));
        $this->assertFalse(is_all('not_numeric', 'abc', 1));

        $this->assertTrue(is_any('not_null', null, [null, 0]));
        $this->assertFalse(is_any('not_null', null, [null]));
        $this->assertTrue(is_any('not_numeric', 1, null));
        $this->assertFalse(is_any('not_numeric', 1, '2'));
    }

    #[Test]
    public function it_negates_a_check(): void
    {
        $this->assertTrue(is_not('array', 'a'));
        $this->assertFalse(is_not('array', []));
        $this->assertTrue(is_not('numeric', 'abc'));
        $this->assertFalse(is_not('numeric', '1'));
        $this->assertTrue(is_not('dir', __DIR__ . '/missing'));
        $this->assertFalse(is_not('dir', __DIR__));
        $this->assertTrue(is_not('dir', __FILE__));
    }

    #[Test]
    public function it_negates_a_check_with_several_arguments(): void
    {
        $this->assertTrue(is_not('in', 'x', 'a,b'));
        $this->assertFalse(is_not('in', 'a', 'a,b'));
        $this->assertTrue(is_not('in', 1, ['1'], true));
        $this->assertTrue(is_not('count_one', [1, 2]));
        $this->assertFalse(is_not('count_one', [1]));
    }

    #[Test]
    public function it_finds_the_same_check_function_again_from_the_cache(): void
    {
        $this->assertTrue(execute_what_function('null', null));
        $this->assertFalse(execute_what_function('null', 1));
        $this->assertTrue(execute_what_function('in', 'a', 'a,b'));
        $this->assertFalse(execute_what_function('in', 'x', 'a,b'));
    }

    #[Test]
    public function it_negates_a_check_starting_with_not(): void
    {
        $this->assertTrue(execute_what_function('not_null', 1));
        $this->assertFalse(execute_what_function('not_null', null));
        $this->assertTrue(execute_what_function('not_in', 'x', 'a,b'));
        $this->assertFalse(execute_what_function('not_count_one', [1]));
    }

    #[Test]
    public function it_negates_a_check_with_the_word_not_anywhere(): void
    {
        $this->assertTrue(execute_what_function('is_not_null', 1));
        $this->assertTrue(execute_what_function('notNull', 1));
        $this->assertFalse(execute_what_function('is_not_null', null));
        $this->assertSame([true, 'is_null'], \JundFauz\EloquentKompact\is_negation_and_get_raw_function('is_not_null'));
        $this->assertSame([false, 'in'], \JundFauz\EloquentKompact\is_negation_and_get_raw_function('in'));
        $this->assertSame([false, 'annotation'], \JundFauz\EloquentKompact\is_negation_and_get_raw_function('annotation'));
    }

    #[Test]
    public function it_finds_an_array_is_function(): void
    {
        $this->assertTrue(execute_what_function('list', [1, 2]));
        $this->assertFalse(execute_what_function('list', ['a' => 1]));
        $this->assertTrue(is_any_of(['a' => 1], 'not_count_one', 'list') === false);
        $this->assertTrue(is_any_of([1], 'not_count_one', 'list'));
    }

    #[Test]
    public function it_does_not_cache_a_check_function_that_is_not_found(): void
    {
        try {
            execute_what_function('kompact_missing_check');
            $this->fail('No exception for the first call');
        } catch (BadMethodCallException) {
        }

        $this->expectException(BadMethodCallException::class);
        execute_what_function('kompact_missing_check');
    }

    #[Test]
    public function it_checks_a_value_against_several_checks(): void
    {
        $this->assertTrue(is_any_of('x', 'int', 'string'));
        $this->assertTrue(is_any_of(null, 'null', 'numeric'));
        $this->assertFalse(is_any_of(1.5, 'int', 'string'));
        $this->assertFalse(is_any_of('x'));
    }

    #[Test]
    public function it_checks_every_check_with_its_arguments(): void
    {
        $this->assertTrue(\JundFauz\EloquentKompact\all(method_exists: [KompactStr::class, 'of'], same_with: [1, 1]));
        $this->assertFalse(\JundFauz\EloquentKompact\all(method_exists: [KompactStr::class, 'of'], same_with: [1, '1']));
        $this->assertTrue(\JundFauz\EloquentKompact\all(not_same_with: [1, '1'], in: ['a', 'a,b']));
        $this->assertTrue(\JundFauz\EloquentKompact\all());
    }

    #[Test]
    public function it_checks_at_least_one_check_with_its_arguments(): void
    {
        $this->assertTrue(\JundFauz\EloquentKompact\any(same_with: [1, 2], in: ['a', 'a,b']));
        $this->assertFalse(\JundFauz\EloquentKompact\any(same_with: [1, 2], method_exists: [KompactStr::class, 'missing']));
        $this->assertFalse(\JundFauz\EloquentKompact\any());
    }

    #[Test]
    public function it_checks_a_null_value_against_several_checks(): void
    {
        $this->assertTrue(is_any_of(null, 'null', 'numeric'));
        $this->assertFalse(is_all_of(null, 'null', 'numeric'));
        $this->assertTrue(is_all_of(null, 'null', 'not_numeric'));
    }

    #[Test]
    public function it_checks_any_string_value_against_several_checks(): void
    {
        $this->assertTrue(is_any_of('THIS_IS_AN_EMPTY_VALUE_TAG', 'string'));
        $this->assertTrue(is_all_of('', 'string', 'not_numeric'));
    }

    #[Test]
    public function it_checks_a_value_against_every_check(): void
    {
        $this->assertTrue(is_all_of('5', 'string', 'numeric'));
        $this->assertFalse(is_all_of('x', 'string', 'numeric'));
        $this->assertTrue(is_all_of('x'));
    }

    #[Test]
    public function it_passes_the_parameters_of_a_named_argument_to_the_check_of_a_value(): void
    {
        $this->assertTrue(is_any_of('max', 'int', in: 'min,max'));
        $this->assertFalse(is_any_of('x', 'int', in: 'min,max'));
        $this->assertTrue(is_all_of('max', 'string', in: 'min,max'));
        $this->assertFalse(is_all_of('max', 'string', in: 'a,b'));
        $this->assertTrue(is_any_of(5, same_with: ['5', 'integer']));
    }

    #[Test]
    public function it_checks_an_array_value_against_several_checks(): void
    {
        $this->assertTrue(is_all_of(['a' => 1], 'array', 'count_one'));
        $this->assertTrue(is_any_of(['a' => 1, 'b' => 2], 'not_count_one', 'list'));
        $this->assertFalse(is_any_of(['a' => 1], 'not_count_one', 'list'));
    }

    #[Test]
    public function it_fails_on_an_unknown_check(): void
    {
        $this->expectException(BadMethodCallException::class);

        is_all('unknown_check', 1);
    }

    #[Test]
    public function it_skips_a_function_that_does_not_return_a_bool(): void
    {
        // count() returns an int and file() array|false, so their is_ functions are used instead
        $this->assertTrue(is_any_of(__FILE__, 'file'));
        $this->assertFalse(is_any_of(__DIR__ . '/missing', 'file'));
        $this->expectException(BadMethodCallException::class);

        is_all('count', [1]);
    }

    #[Test]
    public function it_checks_the_type_of_every_value(): void
    {
        $this->assertTrue(is_all_type_of('int', 1, 2));
        $this->assertTrue(is_all_type_of('int', [1, [2]]));
        $this->assertFalse(is_all_type_of('int', 1, 'a'));
        $this->assertTrue(is_all_type_of(['int', 'string'], 1, 'a'));
        $this->assertFalse(is_all_type_of(['int', 'string'], 1, 1.5));

        $this->assertTrue(is_any_not_type_of('int', 1, 'a'));
        $this->assertFalse(is_any_not_type_of(['int', 'string'], 1, 'a'));
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
    public function it_casts_a_value(): void
    {
        $this->assertSame(5, cast_as('5', 'integer'));
        $this->assertSame('10.50', cast_as('10.5', 'decimal:2'));
        $this->assertTrue(cast_as(1, 'boolean'));
        $this->assertSame([1, 2], cast_as('[1,2]', 'array'));
        $this->assertSame(FunctionsTestStatus::Paid, cast_as('paid', FunctionsTestStatus::class));
        $this->assertSame(FunctionsTestStatus::Paid, cast_as(FunctionsTestStatus::Paid, FunctionsTestStatus::class));
        $this->assertNull(cast_as(null, 'integer'));
    }

    #[Test]
    public function it_returns_the_value_without_a_cast(): void
    {
        $this->assertSame('5', cast_as('5'));
        $this->assertSame('5', cast_as('5', null));
        $this->assertSame([1], cast_as([1]));
        $this->assertNull(cast_as(null));
    }

    #[Test]
    public function it_compares_values_with_a_cast(): void
    {
        $this->assertTrue(same_with('5', 5, 'integer'));
        $this->assertTrue(same_with('10.5', 10.50, 'decimal:2'));
        $this->assertTrue(same_with('paid', FunctionsTestStatus::Paid, FunctionsTestStatus::class));
        $this->assertTrue(same_with(1, '1', 'string'));
        $this->assertFalse(same_with('5', 6, 'integer'));
        $this->assertFalse(not_same_with('5', 5, 'integer'));
        $this->assertTrue(not_same_with('5', 6, 'integer'));
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
    public function it_checks_the_count(): void
    {
        $this->assertTrue(Arr\is_count_zero([]));
        $this->assertTrue(Arr\is_count_zero(new ArrayObject));
        $this->assertFalse(Arr\is_count_zero([null]));

        $this->assertTrue(Arr\is_count_one([null]));
        $this->assertTrue(Arr\is_count_one(new ArrayObject(['a' => 1])));
        $this->assertFalse(Arr\is_count_one([]));
        $this->assertFalse(Arr\is_count_one([1, 2]));

        $this->assertTrue(Arr\is_count_many([1, 2]));
        $this->assertTrue(Arr\is_count_many(new ArrayObject([1, 2, 3])));
        $this->assertFalse(Arr\is_count_many([1]));
        $this->assertFalse(Arr\is_count_many([]));
    }

    #[Test]
    public function it_stringifies_arrays(): void
    {
        $this->assertSame(
            ['user.name', 'k' => ['posts', '1'], '1.5', '1', '', ''],
            Str\stringify([Relation::arrange('name')->relation('user'), 'k' => [Relation::arrange()->relations('posts'), 1], 1.5, true, false, null])
        );
        $this->assertSame([], Str\stringify([]));
    }

    #[Test]
    public function it_calls_a_laravel_str_method_by_name(): void
    {
        $this->assertSame('foo_bar', Str\str('snake', 'FooBar'));
        $this->assertSame('ba', Str\str('replaceFirst', 'a', 'b', 'aa'));
        $this->assertSame('ba', Str\str('replace_first', 'a', 'b', 'aa'));
        $this->assertSame('digits_between', Str\str('snake', 'digitsBetween'));
    }

    #[Test]
    public function it_runs_the_singular_str_method_on_every_parameter(): void
    {
        $this->assertSame(['aB', 'cD'], Str\str('camels', 'a_b', 'c_d'));
        $this->assertSame(['a_b'], Str\str('snakes', 'aB'));
    }

    #[Test]
    public function it_still_calls_a_str_method_whose_name_looks_plural(): void
    {
        $this->assertTrue(Str\str('contains', 'abc', 'b'));
        $this->assertSame('a b', Str\str('words', 'a b c', 2, ''));
    }

    #[Test]
    public function it_chains_the_methods_of_an_object(): void
    {
        $this->assertSame('ABc', (string) \JundFauz\EloquentKompact\chain(KompactStr::of('ab'), 'upper', append: 'c'));
        $this->assertTrue(\JundFauz\EloquentKompact\chain(KompactStr::of('isDirty'), 'snake', startsWith: 'is'));
        $this->assertSame('null', \JundFauz\EloquentKompact\chain(collect(['not', 'null']), diff: [['not']], implode: '_'));
    }

    #[Test]
    public function it_calls_a_chained_method_without_parameters_by_its_name(): void
    {
        $this->assertSame('a', Str\strs(' a ', trim: []));
    }

    #[Test]
    public function it_chains_the_str_methods_of_a_string(): void
    {
        $this->assertSame('A', Str\strs('  a ', 'trim', 'upper'));
        $this->assertTrue(Str\strs('isDirty', 'snake', startsWith: 'is'));
        $this->assertFalse(Str\strs('dirty', 'snake', startsWith: 'is'));
        $this->assertSame('ab', Str\strs('b', addAtStart: 'a'));
    }

    #[Test]
    public function it_calls_a_method_of_the_str_of_the_package_by_name(): void
    {
        $this->assertSame('ab', Str\str('add_at_start', 'b', 'a'));
        $this->assertSame('ab', Str\str('addAtStart', 'b', 'a'));
    }

    #[Test]
    public function it_gives_back_what_a_str_method_of_the_package_returns_when_it_is_not_a_string(): void
    {
        $this->assertTrue(Str\str('is_not_empty', 'a'));
        $this->assertSame(['a', 'b'], Str\str('explode', 'a,b', ',')->toArray());
    }

    #[Test]
    public function it_throws_on_an_unknown_laravel_str_method(): void
    {
        $this->expectException(BadMethodCallException::class);

        Str\str('notAStrMethod', 'a');
    }

    #[Test]
    public function it_trims_the_end_of_every_line(): void
    {
        $this->assertSame("a\n b\n", Str\rtrim_multiline("a  \n b \n"));
        $this->assertSame("a\t\nb", Str\rtrim_multiline("a\t \nb ", ' '));
        $this->assertSame('', Str\rtrim_multiline(''));
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
    public function str_empty_if_false_keeps_the_value_only_when_the_condition_is_truthy(): void
    {
        $this->assertSame('a', Str\empty_if_false(true, 'a'));
        $this->assertSame('a', Str\empty_if_false('x', 'a'));
        $this->assertSame('a', Str\empty_if_false([1], 'a'));

        foreach ([false, null, 0, '', '0', []] as $condition) {
            $this->assertSame('', Str\empty_if_false($condition, 'a'));
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

        $this->assertSame(['name' => 'a', 'active' => true], object_compact($model, 'name', 'active'));
        $this->assertSame(['tags' => ['x']], object_compact($model, 'tags'));
    }

    #[Test]
    public function it_compacts_the_properties_of_any_object(): void
    {
        $object = new stdClass;
        $object->a = 1;
        $object->b = null;

        $this->assertSame(['a' => 1, 'x' => 1], object_compact($object, 'a', x: 'a'));
        $this->assertSame(['b' => null, 'missing' => null], object_compact($object, 'b', 'missing'));
        $this->assertSame([], object_compact($object));
    }

    #[Test]
    public function it_compacts_model_attributes_with_an_alias(): void
    {
        $model = self::model(['name' => 'a', 'email' => 'a@b.c', 'active' => 1]);

        $this->assertSame(['name' => 'a', 'email' => 'a@b.c'], object_compact($model, ['name', 'email']));
        $this->assertSame(['nama' => 'a', 'surel' => 'a@b.c'], object_compact($model, ['nama' => 'name', 'surel' => 'email']));
        $this->assertSame(['nama' => 'a', 'email' => 'a@b.c'], object_compact($model, ['nama' => 'name'], 'email'));
        $this->assertSame(['active' => true, 'nama' => 'a'], object_compact($model, 'active', nama: 'name'));
    }

    #[Test]
    public function it_merges_strings_and_arrays_keeping_the_string_keys(): void
    {
        $this->assertSame(['a', 'k' => 'b', 'c', 'x' => 'y'], Arr\merge_mixed(['a', ['k' => 'b'], ['c'], 'x' => 'y']));
        $this->assertSame([], Arr\merge_mixed([]));
    }

    #[Test]
    public function it_compacts_an_array(): void
    {
        $array = ['a' => 1, 'b' => 2];

        $this->assertSame(['a' => 1, 'c' => null], Arr\compact($array, 'a', 'c'));
        $this->assertSame(['a' => 1, 'x' => 2], Arr\compact($array, 'a', x: 'b'));
        $this->assertSame([], Arr\compact($array));
        $this->assertSame(['a' => 1, 'b' => 2], $array);
    }

    #[Test]
    public function it_compacts_an_array_and_unsets_the_keys(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->assertSame(['a' => 1, 'x' => null], Arr\compact_and_unset($array, 'a', 'x'));
        $this->assertSame(['b' => 2, 'c' => 3], $array);

        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->assertSame(['a' => 1, 'y' => 2], Arr\compact_and_unset($array, 'a', y: 'b'));
        $this->assertSame(['c' => 3], $array);

        $array = ['a' => 1];
        $this->assertSame([], Arr\compact_and_unset($array));
        $this->assertSame(['a' => 1], $array);
    }

    #[Test]
    public function it_keeps_the_given_column_order(): void
    {
        $model = self::model(['name' => 'a', 'email' => 'a@b.c']);

        $this->assertSame(['email', 'name'], array_keys(object_compact($model, 'email', 'name')));
    }

    #[Test]
    public function it_returns_null_for_missing_attributes(): void
    {
        $this->assertSame(['missing' => null], object_compact(self::model([]), 'missing'));
    }

    #[Test]
    public function it_returns_an_empty_array_without_columns(): void
    {
        $this->assertSame([], object_compact(self::model(['name' => 'a'])));
    }

    #[Test]
    public function it_converts_loaded_relations_to_arrays(): void
    {
        $model = self::model(['name' => 'a']);
        $model->setRelation('owner', self::model(['name' => 'b']));

        $this->assertSame(['owner' => ['name' => 'b']], object_compact($model, 'owner'));
    }

    #[Test]
    public function it_checks_a_value_is_not_in_a_list(): void
    {
        $this->assertTrue(is_not('in_array', 'c', ['a', 'b']));
        $this->assertFalse(is_not('in_array', 'a', ['a', 'b']));
        $this->assertFalse(is_not('in_array', '1', [1]));
        $this->assertTrue(is_not('in_array', '1', [1], true));

        $this->assertTrue(is_not('in', 'c', collect(['a', 'b'])));
        $this->assertFalse(is_not('in', 'a', new SupportCollection(['a'])));
        $this->assertFalse(is_not('in', 'a', ['a']));
        $this->assertTrue(is_not('in', '1', collect([1]), true));
    }

    #[Test]
    public function it_checks_a_value_is_in_a_list(): void
    {
        $this->assertTrue(Arr\in('a', ['a', 'b']));
        $this->assertTrue(Arr\in('a', collect(['a'])));
        $this->assertTrue(Arr\in('a', new SupportCollection(['a'])));
        $this->assertFalse(Arr\in('c', ['a', 'b']));
        $this->assertTrue(Arr\in('1', [1]));
        $this->assertFalse(Arr\in('1', collect([1]), true));
    }

    #[Test]
    public function it_casts_a_value_to_an_array(): void
    {
        $this->assertSame(['a', 'b'], Arr\cast(collect(['a', 'b'])));
        $this->assertSame(['a' => 1], Arr\cast(new SupportCollection(['a' => 1])));
        $this->assertSame(['a'], Arr\cast('a'));
        $this->assertSame([], Arr\cast(null));
        $this->assertSame([1, 2], Arr\cast([1, 2]));
    }

    #[Test]
    public function it_builds_and_checks_a_constant_name(): void
    {
        $this->assertSame('Foo\Bar::Baz', Str\constant('Foo\Bar', 'Baz'));
        $this->assertFalse(is_not('defined', Str\constant(\JundFauz\EloquentKompact\Column\ColumnCreator::class, 'Relation')));
        $this->assertTrue(is_not('defined', Str\constant(\JundFauz\EloquentKompact\Column\ColumnCreator::class, 'Unknown')));
    }

    #[Test]
    public function it_gives_null_if_the_condition_is_truthy(): void
    {
        $this->assertNull(null_if(true, 'paid'));
        $this->assertSame('paid', null_if(false, 'paid'));
        $this->assertNull(nullIf(true, 'paid'));
        $this->assertNull(nullIf(1, 'paid'));
        $this->assertNull(nullIf('yes', 'paid'));
        $this->assertSame('paid', nullIf(false, 'paid'));
        $this->assertSame('paid', nullIf(0, 'paid'));
        $this->assertSame('paid', nullIf('', 'paid'));
        $this->assertSame('paid', nullIf(null, 'paid'));
        $this->assertSame([], nullIf([], []));
    }

    #[Test]
    public function it_gives_null_if_the_condition_is_falsy(): void
    {
        $this->assertSame('paid', null_if_not(true, 'paid'));
        $this->assertSame('paid', null_if_not(1, 'paid'));
        $this->assertNull(null_if_not(false, 'paid'));
        $this->assertNull(null_if_not(0, 'paid'));
        $this->assertNull(null_if_not('', 'paid'));
        $this->assertNull(null_if_not(null, 'paid'));
    }

    #[Test]
    public function it_gives_a_value_only_if_another_is_not_null(): void
    {
        $user = (object) ['name' => 'Ana'];
        $data = ['name' => 'Ana'];

        $this->assertSame('Ana', if_not_null($user, $user->name));
        $this->assertSame('Ana', if_not_null($data, $data['name']));
        $this->assertSame('paid', if_not_null(0, 'paid'));
        $this->assertSame('paid', if_not_null('', 'paid'));
        $this->assertNull(if_not_null(null, 'paid'));

        // The value is taken as it is, so the array and the object are not changed
        $this->assertSame(['name' => 'Ana'], $data);
        $this->assertSame(['name' => 'Ana'], (array) $user);
    }

    #[Test]
    public function it_gets_a_value_or_a_default_without_a_warning(): void
    {
        $array = ['a' => 1, 'empty' => null, 'zero' => 0];

        $this->assertSame(1, Arr\get($array['a'], 'x'));
        $this->assertSame(0, Arr\get($array['zero'], 'x'));
        $this->assertSame('x', Arr\get($array['empty'], 'x'));
        $this->assertSame('x', Arr\get($array['missing'], 'x'));
        $this->assertSame([], Arr\get($array['missing']['nested'], []));
        $this->assertSame(1, Arr\get($array['a']));
        $this->assertNull(Arr\get($array['other']));

        $null = null;
        $this->assertSame('x', Arr\get($null['key'], 'x'));
    }

    #[Test]
    public function it_gets_a_value_or_a_default_with_a_cast(): void
    {
        $array = ['a' => '5', 'empty' => null, 'status' => 'paid', 'json' => '[1,2]'];

        $this->assertSame(5, Arr\get($array['a'], 0, 'integer'));
        $this->assertSame(7, Arr\get($array['missing'], '7', 'integer'));
        $this->assertSame(0, Arr\get($array['empty'], '0', 'integer'));
        $this->assertNull(Arr\get($array['other'], null, 'integer'));
        $this->assertSame([1, 2], Arr\get($array['json'], null, 'array'));
        $this->assertSame(FunctionsTestStatus::Paid, Arr\get($array['status'], null, FunctionsTestStatus::class));

        // Without a cast the value is taken as it is
        $this->assertSame('5', Arr\get($array['a'], 0));
    }

    #[Test]
    public function it_concatenates_collections(): void
    {
        $this->assertSame('abcd', Str\concat('a', collect(['b', 'c']), new SupportCollection(['d'])));
        $this->assertSame('abc', Str\concat('a', [collect(['b']), ['c']]));
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
        $this->assertTrue(function_exists('JundFauz\EloquentKompact\String\concat'));
        $this->assertTrue(function_exists('JundFauz\EloquentKompact\Array\pull'));
        $this->assertFalse(function_exists('concat'));
        $this->assertFalse(function_exists('pull'));
    }

    #[Test]
    public function it_gets_a_value_as_a_collection(): void
    {
        $config = ['token' => ['a', 'b']];

        $this->assertInstanceOf(\JundFauz\EloquentKompact\Builder\Collection::class, Arr\array_get_as_collection($config['token'], []));
        $this->assertSame(['a', 'b'], Arr\array_get_as_collection($config['token'], [])->all());
        $this->assertSame([], Arr\array_get_as_collection($config['missing'], [])->all());
        $this->assertSame(['x'], Arr\array_get_as_collection($config['other'], ['x'])->all());
        $this->assertSame([], Arr\array_get_as_collection($config['missing'])->all());
    }

    #[Test]
    public function it_gets_the_list_items_of_an_array(): void
    {
        $this->assertSame([0 => 'a', 5 => 'c'], Arr\get_array_list(['a', 'k' => 'b', 5 => 'c']));
        $this->assertSame([], Arr\get_array_list(['k' => 'b']));
    }

    #[Test]
    public function it_merges_arrays(): void
    {
        $this->assertSame(['a' => 2, 0 => 'x', 1 => 'y'], Arr\merge(['a' => 1, 'x'], ['a' => 2, 'y']));
        $this->assertSame([], Arr\merge());
    }

    #[Test]
    public function it_chains_methods_with_blank_named_arguments(): void
    {
        $this->assertSame(
            'scrap',
            Str\strs('hasScrap', removeStart: 'has', lower: '', trim: []),
        );
        $this->assertSame(
            ['a'],
            \JundFauz\EloquentKompact\chain(collect(['a', 'b']), diff: [['b']], values: null)->all(),
        );
    }

    #[Test]
    public function it_chains_collection_methods_with_collects(): void
    {
        $this->assertSame(
            ['A', 'B'],
            \JundFauz\EloquentKompact\collects(['a', null, 'b'], 'filterFilled', map: [fn (string $v) => strtoupper($v)], values: ''),
        );
        $this->assertSame(
            'a, b',
            \JundFauz\EloquentKompact\collects(['a', 'b'], implodeAsStr: [', ']),
        );
        $this->assertSame(
            6,
            \JundFauz\EloquentKompact\collects([1, 2, 3], 'sum'),
        );
        $this->assertTrue(
            \JundFauz\EloquentKompact\collects(new SupportCollection(['x', 'y']), contains: 'y'),
        );
    }

    #[Test]
    public function it_chains_laravel_arr_methods_with_arrs(): void
    {
        $this->assertSame(
            [1, 2, 3],
            Arr\arrs([[1, [2]], [3]], 'flatten'),
        );
        $this->assertSame(
            ['a' => 1],
            Arr\arrs(['a' => 1, 'b' => null, 'c' => 2], only: [['a', 'b']], whereNotNull: ''),
        );
        $this->assertSame(
            'a, b and c',
            Arr\arrs(['a', 'b', 'c'], join: [', ', ' and ']),
        );
    }

    #[Test]
    public function it_wraps_values_including_null_with_simple_wrap(): void
    {
        $this->assertSame([null], Arr\simple_wrap(null));
        $this->assertSame(['a'], Arr\simple_wrap('a'));
        $this->assertSame(['a', 'b'], Arr\simple_wrap(['a', 'b']));
        $this->assertSame([], Arr\simple_wrap([]));
    }

    #[Test]
    public function it_joins_values_with_strip_and_snake_concat(): void
    {
        $this->assertSame('-', \JundFauz\EloquentKompact\strip);
        $this->assertSame('a-b-c', \JundFauz\EloquentKompact\strip_concat('a', ['b', ['c']]));
        $this->assertSame('a_b_c', \JundFauz\EloquentKompact\snake_concat('a', ['b', ['c']]));
        $this->assertSame('', \JundFauz\EloquentKompact\strip_concat());
        $this->assertSame('', \JundFauz\EloquentKompact\snake_concat());
    }

    #[Test]
    public function it_joins_values_with_concat_class(): void
    {
        $this->assertSame('a/b/c', \JundFauz\EloquentKompact\Helper\Concat::path('a', ['b', ['c']]));
        $this->assertSame('a b', \JundFauz\EloquentKompact\Helper\Concat::word('a', 'b'));
        $this->assertSame('decimal:2', \JundFauz\EloquentKompact\Helper\Concat::schema('decimal', '2'));
        $this->assertSame('posts.user', \JundFauz\EloquentKompact\Helper\Concat::relation('posts', 'user'));
        $this->assertSame('a_b', \JundFauz\EloquentKompact\Helper\Concat::snake('a', 'b'));
        $this->assertSame('a-b', \JundFauz\EloquentKompact\Helper\Concat::strip('a', 'b'));

        $this->expectException(BadMethodCallException::class);
        \JundFauz\EloquentKompact\Helper\Concat::unknown('a', 'b');
    }

    #[Test]
    public function it_casts_objects_arrays_and_dates_with_cast_as(): void
    {
        // Dates & Carbon (including without an active DB connection)
        $previousResolver = Model::getConnectionResolver();
        Model::unsetConnectionResolver();
        try {
            $date = cast_as('2026-01-15 10:20:30', 'datetime');
            $this->assertInstanceOf(Carbon::class, $date);
            $this->assertSame('2026-01-15 10:20:30', $date->format('Y-m-d H:i:s'));

            $carbonInput = Carbon::parse('2026-01-15 10:20:30.123456');
            $castedCarbon = cast_as($carbonInput, 'datetime');
            $this->assertInstanceOf(Carbon::class, $castedCarbon);
            $this->assertSame('123456', $castedCarbon->format('u'));

            $immutable = cast_as($carbonInput, 'immutable_datetime');
            $this->assertInstanceOf(CarbonImmutable::class, $immutable);
            $this->assertSame($carbonInput->getTimestamp(), cast_as($carbonInput, 'timestamp'));
            $this->assertSame('2026-01-15 10:20:30', cast_as(Carbon::parse('2026-01-15 10:20:30'), 'string'));
        } finally {
            Model::setConnectionResolver($previousResolver);
        }

        // Arrays, Collections, Objects & Built-in Eloquent Cast Classes
        $this->assertSame(['a' => 1], cast_as(['a' => 1], 'array'));
        $this->assertSame([1, 2], cast_as(collect([1, 2]), 'array'));
        $this->assertTrue(same_with('[1,2]', [1, 2], 'array'));
        $this->assertTrue(same_with('[1,2]', collect([1, 2]), 'array'));

        $castedCollection = cast_as([1, 2], 'collection');
        $this->assertInstanceOf(SupportCollection::class, $castedCollection);
        $this->assertSame([1, 2], $castedCollection->all());

        $castedObj = cast_as(['a' => 1], 'object');
        $this->assertInstanceOf(stdClass::class, $castedObj);
        $this->assertSame(1, $castedObj->a);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Casts\ArrayObject::class, cast_as(['a' => 1], AsArrayObject::class));
        $this->assertInstanceOf(SupportCollection::class, cast_as(new ArrayObject([1, 2]), AsCollection::class));

        // Custom CastsAttributes (UuidCast) & CastsInboundAttributes
        $uuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');
        $castedUuid = cast_as($uuid, UuidCast::class);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', (string) $castedUuid);
        $this->assertSame('HELLO', cast_as('hello', FunctionsTestUppercaseInboundCast::class));

        // Stringable & BackedEnum unwrapping on primitive casts
        $this->assertSame(5, cast_as(KompactStr::of('5'), 'integer'));
        $this->assertSame([1, 2], cast_as(KompactStr::of('[1,2]'), 'array'));
        $this->assertSame('paid', cast_as(FunctionsTestStatus::Paid, 'string'));
    }
}

class FunctionsTestUppercaseInboundCast implements CastsInboundAttributes
{
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return strtoupper((string) $value);
    }
}

enum FunctionsTestStatus: string
{
    case Paid = 'paid';
}
