<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
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
    public function it_throws_on_unknown_methods(): void
    {
        $this->expectException(BadMethodCallException::class);

        Arrange::unknown('name');
    }
}

final class BlogPost
{
}
