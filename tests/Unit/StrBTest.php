<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use Illuminate\Foundation\Application;
use JundFauz\EloquentKompact\Builder\StrB;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class StrBTest extends TestCase
{
    public static function stringProvider(): iterable
    {
        yield 'initiate empty' => [StrB::initiate(), ''];
        yield 'initiate from builder' => [StrB::initiate(StrB::initiate(' a ')->trim()), 'a'];

        yield 'trim' => [StrB::initiate('  a  ')->trim(), 'a'];
        yield 'trim characters' => [StrB::initiate('/a/b/')->trim('/'), 'a/b'];
        yield 'ltrim' => [StrB::initiate('  a  ')->ltrim(), 'a  '];
        yield 'ltrim characters' => [StrB::initiate('//a/')->ltrim('/'), 'a/'];
        yield 'rtrim' => [StrB::initiate('  a  ')->rtrim(), '  a'];
        yield 'rtrim characters' => [StrB::initiate('/a//')->rtrim('/'), '/a'];

        yield 'replace' => [StrB::initiate('a-b-c')->replace('-', '_'), 'a_b_c'];
        yield 'replace many' => [StrB::initiate('a-b.c')->replace(['-', '.'], '_'), 'a_b_c'];
        yield 'replace regex' => [StrB::initiate('App/Models')->replace('/^app(\/|$)/i', '', true), 'Models'];
        yield 'replace keeps regex text literal' => [StrB::initiate('a.c')->replace('/./', '_'), 'a.c'];
        yield 'remove' => [StrB::initiate('a-b-c')->remove('-'), 'abc'];
        yield 'remove regex' => [StrB::initiate('a1b22c')->remove('/\d+/', true), 'abc'];

        yield 'join' => [StrB::initiate('a')->join('', 'b', 'c'), 'abc'];
        yield 'join delimiter' => [StrB::initiate('a')->join('.', 'b', 'c'), 'a.b.c'];
        yield 'join nothing' => [StrB::initiate('a')->join('.'), 'a'];
        yield 'emptyIfFalse true' => [StrB::initiate('a')->emptyIfFalse(true), 'a'];
        yield 'emptyIfFalse false' => [StrB::initiate('a')->emptyIfFalse(false), ''];

        yield 'singular' => [StrB::initiate('users')->singular(), 'user'];
        yield 'plural' => [StrB::initiate('user')->plural(), 'users'];
        yield 'plural count one' => [StrB::initiate('user')->plural(1), 'user'];
        yield 'pascal' => [StrB::initiate('blog_post')->pascal(), 'BlogPost'];
        yield 'camel' => [StrB::initiate('blog_post')->camel(), 'blogPost'];
        yield 'snake' => [StrB::initiate('BlogPost')->snake(), 'blog_post'];
        yield 'snake delimiter' => [StrB::initiate('BlogPost')->snake('-'), 'blog-post'];
    }

    #[Test]
    #[DataProvider('stringProvider')]
    public function it_builds_strings(StrB $string, string $expected): void
    {
        $this->assertSame($expected, $string->build());
    }

    #[Test]
    public function it_casts_to_the_built_string(): void
    {
        $string = StrB::initiate(' a ')->trim();

        $this->assertSame($string->build(), (string) $string);
    }

    #[Test]
    public function it_returns_a_new_instance_and_keeps_the_original(): void
    {
        $string = StrB::initiate(' a ');
        $trimmed = $string->trim();

        $this->assertNotSame($string, $trimmed);
        $this->assertSame(' a ', $string->build());
        $this->assertSame('a', $trimmed->build());
    }

    #[Test]
    public function it_chains_methods(): void
    {
        $this->assertSame(
            'BlogPosts',
            StrB::initiate('App/blog_post/')->remove('/^app(\/|$)/i', true)->trim('/')->plural()->pascal()->build()
        );
    }

    #[Test]
    public function it_pluralizes_with_the_count_prepended(): void
    {
        if (version_compare(Application::VERSION, '12.0.0', '<')) {
            $this->markTestSkipped('Str::plural() $prependCount requires Laravel 12+.');
        }

        $this->assertSame('2 users', StrB::initiate('user')->plural(2, true)->build());
    }

    #[Test]
    public function it_normalizes_acronyms_in_pascal_case(): void
    {
        if (version_compare(Application::VERSION, '13.0.0', '<')) {
            $this->markTestSkipped('Str::pascal() $normalize requires Laravel 13+.');
        }

        $this->assertSame('CborData', StrB::initiate('CBOR_data')->pascal(true)->build());
    }

    #[Test]
    public function it_throws_on_unknown_methods(): void
    {
        $this->expectException(BadMethodCallException::class);

        StrB::initiate('a')->upper();
    }

    #[Test]
    public function it_is_case_sensitive_on_method_names(): void
    {
        $this->expectException(BadMethodCallException::class);

        StrB::initiate(' a ')->Trim();
    }

    #[Test]
    public function it_throws_on_unknown_static_methods(): void
    {
        $this->expectException(BadMethodCallException::class);

        StrB::create('a');
    }
}
