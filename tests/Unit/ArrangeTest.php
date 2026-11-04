<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use InvalidArgumentException;
use JundFauz\EloquentKompact\Relation\Arrange;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ArrangeTest extends TestCase
{
    #[Test]
    public function it_builds_singular_relation_paths_from_model_classes(): void
    {
        $this->assertSame('blogPost.name', Arrange::relation('name', BlogPost::class));
        $this->assertSame('blogPost', Arrange::relation('', BlogPost::class));
    }

    #[Test]
    public function it_builds_plural_relation_paths_from_model_classes(): void
    {
        $this->assertSame('blogPosts.name', Arrange::relations('name', BlogPost::class));
    }

    #[Test]
    public function it_keeps_names_that_are_not_classes(): void
    {
        $this->assertSame('blogPost.roles.name', Arrange::relation('name', BlogPost::class, 'roles'));
        $this->assertSame('author.roles', Arrange::relation('', 'author', 'roles'));
    }

    #[Test]
    public function it_flattens_nested_relations(): void
    {
        $this->assertSame('author.roles.name', Arrange::relation('name', ['author', ['roles']]));
    }

    #[Test]
    public function it_returns_a_string(): void
    {
        $this->assertIsString(Arrange::relation('name', 'author'));
        $this->assertIsString(Arrange::relations('', 'author'));
    }

    #[Test]
    public function it_keeps_the_prefix_of_a_relation(): void
    {
        $this->assertSame('latestBlogPost.name', Arrange::relation('name', ['latest' => BlogPost::class]));
        $this->assertSame('latestBlogPost', Arrange::relation('', ['latest' => BlogPost::class]));
        $this->assertSame('latestBlogPosts.name', Arrange::relations('name', ['latest' => BlogPost::class]));
    }

    #[Test]
    public function it_mixes_prefixed_relations_with_the_others(): void
    {
        $this->assertSame('blogPost.latestBlogPost.roles.name', Arrange::relation('name', BlogPost::class, ['latest' => BlogPost::class], 'roles'));
    }

    #[Test]
    public function it_keeps_the_prefix_inside_a_list(): void
    {
        $this->assertSame('latestBlogPost.roles.name', Arrange::relation('name', [['latest' => BlogPost::class], ['roles']]));
    }

    #[Test]
    public function it_throws_on_several_prefixes_in_one_array(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Arrange::relation('name', ['latest' => BlogPost::class, 'oldest' => BlogPost::class]);
    }

    #[Test]
    public function it_throws_on_unknown_methods(): void
    {
        $this->expectException(BadMethodCallException::class);

        Arrange::unknown('name');
    }
}

final class BlogPost
{
}
