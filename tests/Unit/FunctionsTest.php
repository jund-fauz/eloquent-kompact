<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

use function JundFauz\EloquentKompact\array_flat;
use function JundFauz\EloquentKompact\comment;
use function JundFauz\EloquentKompact\full_path_on_app;
use function JundFauz\EloquentKompact\is_all_null;
use function JundFauz\EloquentKompact\is_not_array;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\not;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\path_concat;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\schema_concat;
use function JundFauz\EloquentKompact\str_before_last;
use function JundFauz\EloquentKompact\str_concat;
use function JundFauz\EloquentKompact\str_lower;
use function JundFauz\EloquentKompact\str_upper;
use function JundFauz\EloquentKompact\word_concat;
use function JundFauz\EloquentKompact\write_line;

use const JundFauz\EloquentKompact\DISTANCE_LINE;
use const JundFauz\EloquentKompact\NAMESPACE_DELIMITER;
use const JundFauz\EloquentKompact\NEW_LINE;
use const JundFauz\EloquentKompact\PATH_DELIMITER;

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
        $this->assertSame('// a', comment('a'));
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
    public function it_wraps_laravel_str_methods(): void
    {
        $this->assertSame('App\Models', str_before_last('App\Models\User', '\\'));
        $this->assertSame('äöü', str_lower('ÄÖÜ'));
        $this->assertSame('ÄÖÜ', str_upper('äöü'));
    }

    #[Test]
    public function it_defines_the_constants(): void
    {
        $this->assertSame("\n", NEW_LINE);
        $this->assertSame("\n\n", DISTANCE_LINE);
        $this->assertSame('\\', NAMESPACE_DELIMITER);
        $this->assertSame('/', PATH_DELIMITER);
    }

    #[Test]
    public function it_is_only_available_in_the_package_namespace(): void
    {
        $this->assertTrue(function_exists('JundFauz\EloquentKompact\str_concat'));
        $this->assertFalse(function_exists('str_concat'));
    }
}
