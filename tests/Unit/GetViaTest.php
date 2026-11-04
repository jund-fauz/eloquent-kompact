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
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GetViaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('get_via_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });
        Schema::create('get_via_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('title');
        });
        Schema::create('get_via_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable();
            $table->string('body');
        });

        DB::table('get_via_users')->insert(['name' => 'Ana', 'email' => 'ana@example.com']);
        DB::table('get_via_posts')->insert([
            ['user_id' => 1, 'title' => 'First'],
            ['user_id' => null, 'title' => 'No user'],
        ]);
        DB::table('get_via_comments')->insert([
            ['post_id' => 1, 'body' => 'Nice'],
            ['post_id' => 2, 'body' => 'No user'],
            ['post_id' => null, 'body' => 'No post'],
        ]);
    }

    #[Test]
    public function it_gets_a_column_via_nested_relations(): void
    {
        $comment = GetViaComment::find(1);

        $this->assertSame('First', $comment->getVia('title', GetViaPost::class));
        $this->assertSame('Ana', $comment->getVia('name', GetViaPost::class, GetViaUser::class));
    }

    #[Test]
    public function it_gets_its_own_column_without_relations(): void
    {
        $this->assertSame('Nice', GetViaComment::find(1)->getVia('body'));
    }

    #[Test]
    public function it_gets_the_related_model_without_a_column(): void
    {
        $user = GetViaComment::find(1)->getVia('', GetViaPost::class, GetViaUser::class);

        $this->assertInstanceOf(GetViaUser::class, $user);
        $this->assertSame(1, $user->id);
    }

    #[Test]
    public function it_gets_the_model_itself_without_arguments(): void
    {
        $comment = GetViaComment::find(1);

        $this->assertSame($comment, $comment->getVia());
    }

    #[Test]
    public function it_gets_null_when_a_relation_is_missing(): void
    {
        $this->assertNull(GetViaComment::find(2)->getVia('name', GetViaPost::class, GetViaUser::class));
        $this->assertNull(GetViaComment::find(3)->getVia('name', GetViaPost::class, GetViaUser::class));
        $this->assertNull(GetViaComment::find(3)->getVia('', GetViaPost::class));
    }

    #[Test]
    public function it_gets_many_columns_via_nested_relations_in_the_given_order(): void
    {
        $comment = GetViaComment::find(1);

        $this->assertSame(
            ['ana@example.com', 'Ana'],
            $comment->getManyVia(['email', 'name'], GetViaPost::class, GetViaUser::class),
        );
    }

    #[Test]
    public function it_gets_a_null_for_each_column_when_a_relation_is_missing(): void
    {
        $this->assertSame(
            [null, null],
            GetViaComment::find(3)->getManyVia(['name', 'email'], GetViaPost::class, GetViaUser::class),
        );
    }

    #[Test]
    public function it_gets_no_values_without_columns(): void
    {
        $this->assertSame([], GetViaComment::find(1)->getManyVia([], GetViaPost::class));
    }

    #[Test]
    public function it_gets_the_data_through_a_prefixed_relation(): void
    {
        $comment = GetViaComment::find(1);

        $this->assertSame('First', $comment->getVia('title', ['latest' => GetViaPost::class]));
        $this->assertSame('Ana', $comment->getVia('name', ['latest' => GetViaPost::class], GetViaUser::class));
        $this->assertInstanceOf(GetViaPost::class, $comment->getVia('', ['latest' => GetViaPost::class]));
        $this->assertSame(['First'], $comment->getManyVia(['title'], ['latest' => GetViaPost::class]));
    }

    #[Test]
    public function it_has_the_methods_on_the_kompact_models(): void
    {
        foreach (['getVia', 'getManyVia'] as $method) {
            $this->assertTrue(method_exists(KompactModel::class, $method), "KompactModel::$method");
            $this->assertTrue(method_exists(KompactPivot::class, $method), "KompactPivot::$method");
        }
    }
}

class GetViaComment extends Model
{
    use AdditionalQueries;

    protected $table = 'get_via_comments';

    public function latestGetViaPost(): BelongsTo
    {
        return $this->belongsTo(GetViaPost::class, 'post_id');
    }

    public function getViaPost(): BelongsTo
    {
        return $this->belongsTo(GetViaPost::class, 'post_id');
    }
}

class GetViaPost extends Model
{
    protected $table = 'get_via_posts';

    public function getViaUser(): BelongsTo
    {
        return $this->belongsTo(GetViaUser::class, 'user_id');
    }
}

class GetViaUser extends Model
{
    protected $table = 'get_via_users';
}
