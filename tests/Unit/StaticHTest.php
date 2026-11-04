<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use JundFauz\EloquentKompact\Query\Operator;
use JundFauz\EloquentKompact\Query\RawQuery;
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
    public function it_returns_the_foreign_key_on_another_table(): void
    {
        $this->assertInstanceOf(RawQuery::class, StaticHPost::foreignFrom(StaticHCategory::class));
        $this->assertSame('"static_h_categories"."static_h_post_id"', StaticHPost::foreignFrom(StaticHCategory::class)->build());
        $this->assertSame('"static_h_categories"."static_h_admin_post_id"', StaticHAdminPost::foreignFrom(StaticHCategory::class)->build());
        $this->assertSame('"static_h_categories"."from_static_h_post_id"', StaticHPost::foreignFrom(StaticHCategory::class, 'from')->build());
    }

    #[Test]
    public function it_returns_a_column_on_another_table(): void
    {
        $this->assertInstanceOf(RawQuery::class, StaticHPost::qualify('name'));
        $this->assertSame('"static_h_posts"."name"', StaticHPost::qualify('name')->build());
        $this->assertSame('"static_h_admin_posts"."name"', StaticHAdminPost::qualify('name')->build());
        $this->assertSame('"static_h_posts".*', StaticHPost::qualify('*')->build());
        $this->assertSame('"other"."name"', StaticHPost::qualify('other.name')->build());
        $this->assertSame('"static_h_posts"."name" as "n"', StaticHPost::qualify('name as n')->build());
    }

    #[Test]
    public function it_continues_a_raw_query_chain_from_a_qualified_column(): void
    {
        $this->assertSame(
            'SUM(COALESCE("static_h_posts"."qty", 0)) AS total',
            StaticHPost::qualify('qty')->zeroIfNull()->sum()->as('total')->build(),
        );
    }

    #[Test]
    public function it_quotes_for_the_connection_of_the_model(): void
    {
        config(['database.connections.static_h_mysql' => ['driver' => 'mysql', 'database' => 'x', 'prefix' => '']]);

        $this->assertSame('`static_h_mysql_posts`.`name`', StaticHMysqlPost::qualify('name')->build());
        $this->assertSame('`static_h_mysql_posts`.`static_h_post_id`', StaticHPost::foreignFrom(StaticHMysqlPost::class)->build());
    }

    #[Test]
    public function it_finds_the_model_with_or_without_a_trailing_backslash_in_the_config(): void
    {
        $blueprint = $this->createStub(Blueprint::class);
        $blueprint->method('getTable')->willReturn('static_h_posts');

        foreach ([__NAMESPACE__, __NAMESPACE__ . '\\'] as $namespace) {
            config(['eloquent-kompact.path.model' => $namespace]);

            $this->assertSame(StaticHPost::class, StaticHPost::getModel($blueprint));
        }
    }

    #[Test]
    public function it_uses_the_table_of_the_model(): void
    {
        $this->assertSame('"custom"."static_h_post_id"', StaticHPost::foreignFrom(StaticHCustomTable::class)->build());
        $this->assertSame('"custom"."name"', StaticHCustomTable::qualify('name')->build());
    }

    #[Test]
    public function it_gives_a_column_that_a_join_quotes_and_prefixes(): void
    {
        $join = fn () => StaticHPost::query()
            ->leftJoin('static_h_categories', 'static_h_posts.id', '=', StaticHPost::foreignFrom(StaticHCategory::class))
            ->toSql();

        $this->assertStringEndsWith('= "static_h_categories"."static_h_post_id"', $join());

        $connection = DB::connection();
        $connection->setTablePrefix('app_');

        try {
            $this->assertStringEndsWith('= "app_static_h_categories"."static_h_post_id"', $join());
            $this->assertStringEndsWith(
                'having SUM("app_static_h_posts"."qty") > 1',
                StaticHPost::query()->havingRaw(StaticHPost::qualify('qty')->sum()->compare('1', Operator::MoreThan))->toSql(),
            );
        } finally {
            $connection->setTablePrefix('');
        }
    }

    #[Test]
    public function it_returns_the_relation_name_with_a_prefix(): void
    {
        $this->assertSame('latestStaticHPost', StaticHPost::singular('latest'));
        $this->assertSame('latestStaticHPosts', StaticHPost::plural('latest'));
        $this->assertSame('latestStaticHAdminPost', StaticHAdminPost::singular('latest'));
    }

    #[Test]
    public function it_returns_a_path_with_a_prefixed_relation(): void
    {
        $this->assertSame('staticHPost.latestStaticHCategory', StaticHPost::relationTo(['latest' => StaticHCategory::class]));
        $this->assertSame('staticHPost.latestStaticHCategory.roles.name', StaticHPost::columnTo('name', ['latest' => StaticHCategory::class], 'roles'));
    }

    #[Test]
    public function it_returns_the_base_name_of_the_class(): void
    {
        $this->assertSame('StaticHPost', StaticHPost::baseName());
        $this->assertSame('StaticHAdminPost', StaticHAdminPost::baseName());
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
    public static function columnTo(string $columnName, string|array ...$modelClasses): string
    {
        return 'overridden';
    }
}

class StaticHCustomTable extends Model
{
    use StaticH;

    protected $table = 'custom';
}

class StaticHMysqlPost extends Model
{
    use StaticH;

    protected $connection = 'static_h_mysql';
}

class StaticHCategory extends Model
{
    use StaticH;
}
