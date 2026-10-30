<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Stringable;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

class StrTest extends TestCase
{
    public static function stringProvider(): iterable
    {
        yield 'of empty' => [Str::of(), ''];
        yield 'of from builder' => [Str::of(Str::of(' a ')->trim()), 'a'];

        yield 'trim' => [Str::of('  a  ')->trim(), 'a'];
        yield 'trim characters' => [Str::of('/a/b/')->trim('/'), 'a/b'];
        yield 'ltrim' => [Str::of('  a  ')->ltrim(), 'a  '];
        yield 'rtrim' => [Str::of('  a  ')->rtrim(), '  a'];

        yield 'lower' => [Str::of('BlogPost')->lower(), 'blogpost'];
        yield 'lower multibyte' => [Str::of('ÄÖÜ')->lower(), 'äöü'];
        yield 'upper' => [Str::of('BlogPost')->upper(), 'BLOGPOST'];
        yield 'upper multibyte' => [Str::of('äöü')->upper(), 'ÄÖÜ'];

        yield 'replace' => [Str::of('a-b-c')->replace('-', '_'), 'a_b_c'];
        yield 'replace many' => [Str::of('a-b.c')->replace(['-', '.'], '_'), 'a_b_c'];
        yield 'replace keeps regex text literal' => [Str::of('a.c')->replace('/./', '_'), 'a.c'];
        yield 'replace case insensitive' => [Str::of('App/Models')->replace('app', 'src', false), 'src/Models'];
        yield 'replaceByRegex' => [Str::of('App/Models')->replaceByRegex('/^app(\/|$)/i', ''), 'Models'];
        yield 'replaceByRegex many' => [Str::of('a1b2')->replaceByRegex(['/\d/', '/b/'], ['_', 'B']), 'a_B_'];
        yield 'replaceByRegex no match' => [Str::of('abc')->replaceByRegex('/\d/', '_'), 'abc'];
        yield 'remove' => [Str::of('a-b-c')->remove('-'), 'abc'];
        yield 'removeByRegex' => [Str::of('a1b22c')->removeByRegex('/\d+/'), 'abc'];
        yield 'removeByRegex no match' => [Str::of('abc')->removeByRegex('/\d+/'), 'abc'];
        yield 'replaceStart' => [Str::of('app/Models/app')->replaceStart('app/', 'src/'), 'src/Models/app'];
        yield 'replaceEnd' => [Str::of('app/Models/app')->replaceEnd('/app', '/src'), 'app/Models/src'];
        yield 'removeStart' => [Str::of('app/Models/app')->removeStart('app/'), 'Models/app'];
        yield 'removeStart only at start' => [Str::of('Models/app')->removeStart('app'), 'Models/app'];
        yield 'removeStart empty' => [Str::of('app')->removeStart(''), 'app'];
        yield 'removeEnd' => [Str::of('app/Models/app')->removeEnd('/app'), 'app/Models'];
        yield 'removeEnd only at end' => [Str::of('app/Models')->removeEnd('app'), 'app/Models'];
        yield 'removeEnd empty' => [Str::of('app')->removeEnd(''), 'app'];

        yield 'join' => [Str::of('a')->join('', 'b', 'c'), 'abc'];
        yield 'join delimiter' => [Str::of('a')->join('.', 'b', 'c'), 'a.b.c'];
        yield 'join nothing' => [Str::of('a')->join('.'), 'a'];
        yield 'concat' => [Str::of('a')->concat('b', 'c'), 'abc'];
        yield 'add' => [Str::of('a')->add('b', 'c'), 'abc'];
        yield 'add nothing' => [Str::of('a')->add(), 'a'];
        yield 'add empty' => [Str::of()->add('a', '', 'b'), 'ab'];
        yield 'addAtStart' => [Str::of('c')->addAtStart('a', 'b'), 'abc'];
        yield 'addAtStart nothing' => [Str::of('a')->addAtStart(), 'a'];
        yield 'addNamespaceAtStart' => [Str::of('User')->addNamespaceAtStart('App', 'Models'), 'App\Models\User'];
        yield 'addNamespaceAtStart trims backslashes' => [Str::of('\User')->addNamespaceAtStart('\App\\', 'Models\\'), 'App\Models\User'];

        yield 'emptyIfFalse true' => [Str::of('a')->emptyIfFalse(true), 'a'];
        yield 'emptyIfFalse false' => [Str::of('a')->emptyIfFalse(false), ''];
        yield 'emptyIfFalse truthy int' => [Str::of('a')->emptyIfFalse(1), 'a'];
        yield 'emptyIfFalse zero' => [Str::of('a')->emptyIfFalse(0), ''];
        yield 'emptyIfFalse string zero' => [Str::of('a')->emptyIfFalse('0'), ''];
        yield 'emptyIfFalse null' => [Str::of('a')->emptyIfFalse(null), ''];
        yield 'emptyIfFalse empty array' => [Str::of('a')->emptyIfFalse([]), ''];
        yield 'emptyIfFalse object' => [Str::of('a')->emptyIfFalse(new stdClass), 'a'];

        yield 'singular' => [Str::of('users')->singular(), 'user'];
        yield 'plural' => [Str::of('user')->plural(), 'users'];
        yield 'pascal' => [Str::of('blog_post')->pascal(), 'BlogPost'];
        yield 'camel' => [Str::of('blog_post')->camel(), 'blogPost'];
        yield 'snake' => [Str::of('BlogPost')->snake(), 'blog_post'];

        yield 'beforeLast' => [Str::of('App/Models/User')->beforeLast('/'), 'App/Models'];
        yield 'afterLast' => [Str::of('App/Models/User')->afterLast('/'), 'User'];
        yield 'between' => [Str::of('[a] [b]')->between('[', ']'), 'a] [b'];
    }

    #[Test]
    #[DataProvider('stringProvider')]
    public function it_builds_strings(Str $string, string $expected): void
    {
        $this->assertSame($expected, $string->toString());
    }

    #[Test]
    public function it_is_a_laravel_stringable(): void
    {
        $this->assertInstanceOf(Stringable::class, Str::of('a'));
    }

    #[Test]
    public function it_returns_its_own_class_from_added_methods(): void
    {
        $string = Str::of('a');

        $this->assertInstanceOf(Str::class, $string->replaceByRegex('/a/', 'b'));
        $this->assertInstanceOf(Str::class, $string->removeByRegex('/a/'));
        $this->assertInstanceOf(Str::class, $string->join('.', 'b'));
        $this->assertInstanceOf(Str::class, $string->add('b'));
        $this->assertInstanceOf(Str::class, $string->addAtStart('b'));
        $this->assertInstanceOf(Str::class, $string->addNamespaceAtStart('b'));
        $this->assertInstanceOf(Str::class, $string->emptyIfFalse(true));
        $this->assertInstanceOf(Str::class, $string->relation());
        $this->assertInstanceOf(Str::class, $string->concat('b'));
        $this->assertInstanceOf(Str::class, $string->removeStart('a'));
        $this->assertInstanceOf(Str::class, $string->removeEnd('a'));
    }

    #[Test]
    public function it_appends_the_relation_token(): void
    {
        $this->assertSame('user_id', Str::of('user')->relation()->toString());
    }

    #[Test]
    public function it_compares_strictly(): void
    {
        $this->assertTrue(Str::of('a')->sameWith('a'));
        $this->assertTrue(Str::of('a')->sameWith(Str::of('a')));
        $this->assertFalse(Str::of('a')->sameWith('b'));
        $this->assertFalse(Str::of('1')->sameWith(1));
        $this->assertFalse(Str::of('')->sameWith(null));

        $this->assertTrue(Str::of('a')->notSameWith('b'));
        $this->assertTrue(Str::of('1')->notSameWith(1));
        $this->assertFalse(Str::of('a')->notSameWith('a'));
        $this->assertFalse(Str::of('a')->notSameWith(Str::of('a')));
    }

    #[Test]
    public function it_compares_with_a_laravel_stringable(): void
    {
        $this->assertTrue(Str::of('a')->sameWith(new Stringable('a')));
        $this->assertTrue(Str::of('a')->sameWith(\Illuminate\Support\Str::of('a')));
        $this->assertFalse(Str::of('a')->sameWith(new Stringable('b')));

        $this->assertTrue(Str::of('a')->notSameWith(new Stringable('b')));
        $this->assertFalse(Str::of('a')->notSameWith(new Stringable('a')));
    }

    #[Test]
    public function it_does_not_unwrap_other_stringable_objects(): void
    {
        $other = new class implements \Stringable {
            public function __toString(): string
            {
                return 'a';
            }
        };

        $this->assertFalse(Str::of('a')->sameWith($other));
        $this->assertTrue(Str::of('a')->notSameWith($other));
    }

    #[Test]
    public function it_casts_to_string(): void
    {
        $string = Str::of(' a ')->trim();

        $this->assertSame($string->toString(), (string) $string);
    }

    #[Test]
    public function it_returns_a_new_instance_and_keeps_the_original(): void
    {
        $string = Str::of(' a ');
        $added = $string->add('b');

        $this->assertNotSame($string, $added);
        $this->assertSame(' a ', $string->toString());
        $this->assertSame(' a b', $added->toString());
    }

    #[Test]
    public function it_chains_methods(): void
    {
        $this->assertSame(
            'BlogPosts',
            Str::of('App/blog_post/')->removeByRegex('/^app(\/|$)/i')->trim('/')->plural()->pascal()->toString()
        );
    }

    #[Test]
    public function it_pluralizes_with_the_count_prepended(): void
    {
        if (version_compare(Application::VERSION, '12.0.0', '<')) {
            $this->markTestSkipped('Str::plural() $prependCount requires Laravel 12+.');
        }

        $this->assertSame('2 users', Str::of('user')->plural(2, true)->toString());
    }

    #[Test]
    public function it_throws_on_unknown_methods(): void
    {
        $this->expectException(BadMethodCallException::class);

        Str::of('a')->unknownMethod();
    }
}
