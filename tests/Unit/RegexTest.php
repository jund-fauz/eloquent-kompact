<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use JundFauz\EloquentKompact\Regex\Quantifier;
use JundFauz\EloquentKompact\Regex\Regex;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class RegexTest extends TestCase
{
    public static function patternProvider(): iterable
    {
        yield 'create empty' => [Regex::create(), '//'];
        yield 'create' => [Regex::create('abc'), '/abc/'];
        yield 'create from regex' => [Regex::create(Regex::create('abc')), '/abc/'];

        yield 'exact' => [Regex::create('id')->exact(), '/^id$/'];
        yield 'exact with parts' => [Regex::create()->exact('a', 'b'), '/^ab$/'];

        yield 'startsWith' => [Regex::create()->startsWith('app'), '/^app/'];
        yield 'startsWith many' => [Regex::create()->startsWith('app', '_'), '/^app_/'];
        yield 'startsWith empty' => [Regex::create()->startsWith(), '/^/'];
        yield 'startsWith after pattern' => [Regex::create('a')->startsWith('b'), '/^ab/'];
        yield 'startsWith nested' => [Regex::create()->startsWith(Regex::create()->range('a', 'z')), '/^a-z/'];

        yield 'endsWith' => [Regex::create()->endsWith('_at'), '/_at$/'];
        yield 'endsWith empty' => [Regex::create()->endsWith(), '/$/'];

        yield 'exceptWord' => [Regex::create()->exceptWord('id'), '/(?!id)/'];
        yield 'contain' => [Regex::create()->contain(Quantifier::oneOrMore, Regex::create()->range('a', 'z')), '/[a-z]+/'];
        yield 'contain string quantifier' => [Regex::create()->contain('{2}', '0-9'), '/[0-9]{2}/'];
        yield 'range' => [Regex::create()->range('0', '9'), '/0-9/'];
    }

    #[Test]
    #[DataProvider('patternProvider')]
    public function it_builds_patterns(Regex $regex, string $expected): void
    {
        $this->assertSame($expected, $regex->build());
    }

    #[Test]
    public function it_matches_only_at_the_start(): void
    {
        $regex = Regex::create()->startsWith('app');

        $this->assertSame(1, preg_match($regex, 'app_user'));
        $this->assertSame(0, preg_match($regex, 'my_app'));
    }

    #[Test]
    public function it_returns_a_new_instance_and_keeps_the_original(): void
    {
        $regex = Regex::create('a');
        $started = $regex->startsWith();

        $this->assertNotSame($regex, $started);
        $this->assertSame('/a/', $regex->build());
        $this->assertSame('/^a/', $started->build());
    }
}
