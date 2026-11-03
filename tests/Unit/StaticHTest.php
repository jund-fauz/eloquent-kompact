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

    #[Test]
    public function it_builds_a_relation_path_starting_from_the_model(): void
    {
        $this->assertSame('staticHPost.staticHCategory', StaticHPost::relationTo(StaticHCategory::class));
        $this->assertSame(
            'staticHPost.staticHCategory.staticHAdminPost',
            StaticHPost::relationTo(StaticHCategory::class, StaticHAdminPost::class),
        );
    }

    #[Test]
    public function it_returns_the_singular_name_for_a_relation_path_without_models(): void
    {
        $this->assertSame('staticHPost', StaticHPost::relationTo());
    }

    #[Test]
    public function it_keeps_relation_names_in_a_relation_path(): void
    {
        $this->assertSame('staticHPost.roles', StaticHPost::relationTo('roles'));
        $this->assertSame('staticHPost.roles.staticHCategory', StaticHPost::relationTo('roles', StaticHCategory::class));
    }

    #[Test]
    public function it_starts_the_relation_path_from_a_subclass(): void
    {
        $this->assertSame('staticHAdminPost.staticHCategory', StaticHAdminPost::relationTo(StaticHCategory::class));
    }

    #[Test]
    public function it_builds_a_column_path_starting_from_the_model(): void
    {
        $this->assertSame('staticHPost.name', StaticHPost::columnTo('name'));
        $this->assertSame('staticHPost.staticHCategory.name', StaticHPost::columnTo('name', StaticHCategory::class));
        $this->assertSame(
            'staticHPost.staticHCategory.staticHAdminPost.name',
            StaticHPost::columnTo('name', StaticHCategory::class, StaticHAdminPost::class),
        );
    }

    #[Test]
    public function it_keeps_relation_names_in_a_column_path(): void
    {
        $this->assertSame('staticHPost.roles.name', StaticHPost::columnTo('name', 'roles'));
    }

    #[Test]
    public function it_builds_a_relation_path_for_an_empty_column(): void
    {
        $this->assertSame('staticHPost.roles', StaticHPost::columnTo('', 'roles'));
        $this->assertSame('staticHPost', StaticHPost::columnTo(''));
    }

    #[Test]
    public function it_starts_the_column_path_from_a_subclass(): void
    {
        $this->assertSame('staticHAdminPost.name', StaticHAdminPost::columnTo('name'));
    }

    #[Test]
    public function it_uses_an_overridden_column_path_for_the_relation_path(): void
    {
        $this->assertSame('overridden', StaticHOverriddenPost::relationTo('roles'));
    }

    #[Test]
    public function it_returns_the_foreign_key_column(): void
    {
        $this->assertSame('static_h_post_id', StaticHPost::foreign());
    }

    #[Test]
    public function it_returns_the_foreign_key_column_with_a_prefix(): void
    {
        $this->assertSame('from_static_h_post_id', StaticHPost::foreign('from'));
        $this->assertSame('static_h_post_id', StaticHPost::foreign(''));
    }

    #[Test]
    public function it_returns_the_foreign_key_column_of_a_subclass(): void
    {
        $this->assertSame('static_h_admin_post_id', StaticHAdminPost::foreign());
    }

    #[Test]
    public function it_ignores_the_morph_alias_in_the_foreign_key_column(): void
    {
        Relation::morphMap(['post' => StaticHPost::class]);

        $this->assertSame('static_h_post_id', StaticHPost::foreign());
    }

    #[Test]
    public function it_always_ends_the_foreign_key_column_with_the_relation_token(): void
    {
        $this->assertSame('static_h_custom_key_post_id', StaticHCustomKeyPost::foreign());
    }
}

class StaticHPost extends Model
{
    use StaticH;
}

class StaticHAdminPost extends StaticHPost {}

class StaticHCustomKeyPost extends StaticHPost
{
    protected $primaryKey = 'uuid';
}

class StaticHOverriddenPost extends StaticHPost
{
    public static function columnTo(string $columnName, string ...$modelClasses): string
    {
        return 'overridden';
    }
}

class StaticHCategory extends Model
{
    use StaticH;
}
