<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Model\Traits\RelationData;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RelationDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('relation_data_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });
        Schema::create('relation_data_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('title');
        });
        Schema::create('relation_data_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable();
            $table->string('body');
        });

        DB::table('relation_data_users')->insert(['name' => 'Ana', 'email' => 'ana@example.com']);
        DB::table('relation_data_posts')->insert([
            ['user_id' => 1, 'title' => 'First'],
            ['user_id' => null, 'title' => 'No user'],
        ]);
        DB::table('relation_data_comments')->insert([
            ['post_id' => 1, 'body' => 'Nice'],
            ['post_id' => 2, 'body' => 'No user'],
            ['post_id' => null, 'body' => 'No post'],
        ]);
    }

    #[Test]
    public function it_gets_a_column_via_nested_relations(): void
    {
        $comment = RelationDataComment::find(1);

        $this->assertSame('First', $comment->getVia('title', RelationDataPost::class));
        $this->assertSame('Ana', $comment->getVia('name', RelationDataPost::class, RelationDataUser::class));
    }

    #[Test]
    public function it_gets_its_own_column_without_relations(): void
    {
        $this->assertSame('Nice', RelationDataComment::find(1)->getVia('body'));
    }

    #[Test]
    public function it_gets_the_related_model_without_a_column(): void
    {
        $user = RelationDataComment::find(1)->getVia('', RelationDataPost::class, RelationDataUser::class);

        $this->assertInstanceOf(RelationDataUser::class, $user);
        $this->assertSame(1, $user->id);
    }

    #[Test]
    public function it_gets_the_model_itself_without_arguments(): void
    {
        $comment = RelationDataComment::find(1);

        $this->assertSame($comment, $comment->getVia());
    }

    #[Test]
    public function it_gets_null_when_a_relation_is_missing(): void
    {
        $this->assertNull(RelationDataComment::find(2)->getVia('name', RelationDataPost::class, RelationDataUser::class));
        $this->assertNull(RelationDataComment::find(3)->getVia('name', RelationDataPost::class, RelationDataUser::class));
        $this->assertNull(RelationDataComment::find(3)->getVia('', RelationDataPost::class));
    }

    #[Test]
    public function it_gets_many_columns_via_nested_relations_in_the_given_order(): void
    {
        $comment = RelationDataComment::find(1);

        $this->assertSame(
            ['ana@example.com', 'Ana'],
            $comment->getManyVia(['email', 'name'], RelationDataPost::class, RelationDataUser::class),
        );
    }

    #[Test]
    public function it_gets_a_null_for_each_column_when_a_relation_is_missing(): void
    {
        $this->assertSame(
            [null, null],
            RelationDataComment::find(3)->getManyVia(['name', 'email'], RelationDataPost::class, RelationDataUser::class),
        );
    }

    #[Test]
    public function it_gets_no_values_without_columns(): void
    {
        $this->assertSame([], RelationDataComment::find(1)->getManyVia([], RelationDataPost::class));
    }

    #[Test]
    public function it_keeps_the_deprecated_names(): void
    {
        $comment = RelationDataComment::find(1);

        $this->assertSame('Ana', $comment->getDataFromRelation('name', RelationDataPost::class, RelationDataUser::class));
        $this->assertSame($comment, $comment->getDataFromRelation());
        $this->assertSame(
            ['Ana', 'ana@example.com'],
            $comment->getDatasFromRelation(['name', 'email'], RelationDataPost::class, RelationDataUser::class),
        );
    }

    #[Test]
    public function it_keeps_the_methods_on_the_deprecated_trait(): void
    {
        $comment = RelationDataDeprecatedComment::find(1);

        $this->assertSame('Ana', $comment->getVia('name', RelationDataPost::class, RelationDataUser::class));
        $this->assertSame(['First'], $comment->getManyVia(['title'], RelationDataPost::class));
        $this->assertSame('First', $comment->getDataFromRelation('title', RelationDataPost::class));
        $this->assertSame(['First'], $comment->getDatasFromRelation(['title'], RelationDataPost::class));
    }

    #[Test]
    public function it_gets_every_additional_query_from_the_deprecated_trait(): void
    {
        $this->assertTrue(RelationDataDeprecatedComment::find(1)->sameWith('body', 'Nice'));
        $this->assertSame(
            [1],
            RelationDataDeprecatedComment::whereInRelation('relationDataPost', 'title', ['First'])->pluck('id')->all(),
        );
    }

    #[Test]
    public function it_allows_both_traits_on_a_model_in_any_order(): void
    {
        $this->assertSame('First', RelationDataBothTraitsComment::find(1)->getVia('title', RelationDataPost::class));
        $this->assertSame('First', RelationDataBothTraitsReversedComment::find(1)->getVia('title', RelationDataPost::class));
    }

    #[Test]
    public function it_has_the_methods_on_the_kompact_models(): void
    {
        foreach (['getVia', 'getManyVia', 'getDataFromRelation', 'getDatasFromRelation'] as $method) {
            $this->assertTrue(method_exists(KompactModel::class, $method), "KompactModel::$method");
            $this->assertTrue(method_exists(KompactPivot::class, $method), "KompactPivot::$method");
        }
    }
}

class RelationDataComment extends Model
{
    use AdditionalQueries;

    protected $table = 'relation_data_comments';

    public function relationDataPost(): BelongsTo
    {
        return $this->belongsTo(RelationDataPost::class, 'post_id');
    }
}

class RelationDataDeprecatedComment extends Model
{
    use RelationData;

    protected $table = 'relation_data_comments';

    public function relationDataPost(): BelongsTo
    {
        return $this->belongsTo(RelationDataPost::class, 'post_id');
    }
}

class RelationDataBothTraitsComment extends Model
{
    use AdditionalQueries, RelationData;

    protected $table = 'relation_data_comments';

    public function relationDataPost(): BelongsTo
    {
        return $this->belongsTo(RelationDataPost::class, 'post_id');
    }
}

class RelationDataBothTraitsReversedComment extends Model
{
    use RelationData, AdditionalQueries;

    protected $table = 'relation_data_comments';

    public function relationDataPost(): BelongsTo
    {
        return $this->belongsTo(RelationDataPost::class, 'post_id');
    }
}

class RelationDataPost extends Model
{
    protected $table = 'relation_data_posts';

    public function relationDataUser(): BelongsTo
    {
        return $this->belongsTo(RelationDataUser::class, 'user_id');
    }
}

class RelationDataUser extends Model
{
    protected $table = 'relation_data_users';
}
