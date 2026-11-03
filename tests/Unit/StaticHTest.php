<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use JundFauz\EloquentKompact\Model\Traits\StaticH;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StaticHTest extends TestCase
{
    protected function tearDown(): void
    {
        Relation::morphMap([], false);

        parent::tearDown();
    }

    #[Test]
    public function it_returns_the_table_name(): void
    {
        $this->assertSame('static_h_posts', StaticHPost::tableName());
    }

    #[Test]
    public function it_returns_the_class_as_morph_class(): void
    {
        $this->assertSame(StaticHPost::class, StaticHPost::morphClass());
    }

    #[Test]
    public function it_returns_the_morph_alias_as_morph_class(): void
    {
        Relation::morphMap(['post' => StaticHPost::class]);

        $this->assertSame('post', StaticHPost::morphClass());
    }

    #[Test]
    public function it_returns_the_singular_relation_name(): void
    {
        $this->assertSame('staticHPost', StaticHPost::singular());
    }

    #[Test]
    public function it_returns_the_plural_relation_name(): void
    {
        $this->assertSame('staticHPosts', StaticHPost::plural());
        $this->assertSame('staticHCategories', StaticHCategory::plural());
    }

    #[Test]
    public function it_returns_the_relation_names_of_a_subclass(): void
    {
        $this->assertSame('staticHAdminPost', StaticHAdminPost::singular());
        $this->assertSame('staticHAdminPosts', StaticHAdminPost::plural());
    }

    #[Test]
    public function it_ignores_the_morph_alias_in_the_relation_names(): void
    {
        Relation::morphMap(['post' => StaticHPost::class]);

        $this->assertSame('staticHPost', StaticHPost::singular());
        $this->assertSame('staticHPosts', StaticHPost::plural());
    }
}

class StaticHPost extends Model
{
    use StaticH;
}

class StaticHAdminPost extends StaticHPost {}

class StaticHCategory extends Model
{
    use StaticH;
}
