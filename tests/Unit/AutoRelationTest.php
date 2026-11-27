<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Article;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\ArticleLabelConnector;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Author;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\AuthorPostConnector;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\CastingConnector;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\CastingStemConnector;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Comment;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\KeptAuthor;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\KeptComment;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\KeptPost;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\KeptTag;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Label;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\LegacyReviewer;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Note;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\ParentLedger;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\PermanentBadge;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Post;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\StemLedger;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Tag;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Writer;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AutoRelationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['eloquent-kompact.path.model' => 'JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation']);
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        Schema::create('ar_writers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('ar_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('writer_id')->nullable();
            $table->foreignId('editor_writer_id')->nullable();
            $table->string('title');
        });
        Schema::create('ar_notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');
            $table->string('body');
        });
        Schema::create('ar_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        DB::table('ar_writers')->insert([['name' => 'Ana'], ['name' => 'Budi']]);
        DB::table('ar_articles')->insert([
            ['writer_id' => 1, 'editor_writer_id' => 2, 'title' => 'First'],
            ['writer_id' => 1, 'editor_writer_id' => null, 'title' => 'Second'],
            ['writer_id' => 2, 'editor_writer_id' => 1, 'title' => 'Third'],
        ]);
        DB::table('ar_notes')->insert([
            ['notable_type' => Article::class, 'notable_id' => 1, 'body' => 'Nice'],
            ['notable_type' => Article::class, 'notable_id' => 1, 'body' => 'Good'],
            ['notable_type' => Writer::class, 'notable_id' => 1, 'body' => 'Other'],
        ]);
    }

    protected function tearDown(): void
    {
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        parent::tearDown();
    }

    #[Test]
    public function it_resolves_a_belongs_to_relation(): void
    {
        $article = Article::find(1);

        $this->assertInstanceOf(BelongsTo::class, $article->writer());
        $this->assertSame('writer_id', $article->writer()->getForeignKeyName());
        $this->assertSame('Ana', $article->writer->name);
    }

    #[Test]
    public function it_resolves_a_prefixed_belongs_to_relation(): void
    {
        $article = Article::find(1);

        $this->assertSame('editor_writer_id', $article->editorWriter()->getForeignKeyName());
        $this->assertSame('Budi', $article->editorWriter->name);
        $this->assertNull(Article::find(2)->editorWriter);
    }

    #[Test]
    public function it_resolves_a_has_one_relation(): void
    {
        $writer = Writer::find(1);

        $this->assertInstanceOf(HasOne::class, $writer->article());
        $this->assertSame('writer_id', $writer->article()->getForeignKeyName());
        $this->assertSame('First', $writer->article->title);

        $this->assertInstanceOf(HasOne::class, $writer->editorArticle());
        $this->assertSame('editor_writer_id', $writer->editorArticle()->getForeignKeyName());
        $this->assertSame('Third', $writer->editorArticle->title);
    }

    #[Test]
    public function it_throws_on_a_singular_relation_without_a_column(): void
    {
        $this->expectException(BadMethodCallException::class);

        Writer::find(1)->label();
    }

    #[Test]
    public function it_resolves_a_has_many_relation(): void
    {
        $writer = Writer::find(1);

        $this->assertInstanceOf(HasMany::class, $writer->articles());
        $this->assertSame('writer_id', $writer->articles()->getForeignKeyName());
        $this->assertSame(['First', 'Second'], $writer->articles->pluck('title')->all());
    }

    #[Test]
    public function it_resolves_a_prefixed_has_many_relation(): void
    {
        $writer = Writer::find(1);

        $this->assertSame('editor_writer_id', $writer->editorArticles()->getForeignKeyName());
        $this->assertSame(['Third'], $writer->editorArticles->pluck('title')->all());
    }

    #[Test]
    public function it_resolves_a_morph_many_relation(): void
    {
        $article = Article::find(1);

        $this->assertInstanceOf(MorphMany::class, $article->notes());
        $this->assertSame(['Nice', 'Good'], $article->notes->pluck('body')->all());
        $this->assertSame(['Other'], Writer::find(1)->notes->pluck('body')->all());
    }

    #[Test]
    public function it_throws_on_a_plural_relation_without_a_column_or_a_morph(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(sprintf(
            "Relation %s::labels can't be resolved: the column ar_labels.writer_id doesn't exist and %s doesn't have a single morphTo.",
            Writer::class,
            Label::class,
        ));

        Writer::find(1)->labels();
    }

    #[Test]
    public function it_translates_the_error_of_a_relation_that_cannot_be_resolved(): void
    {
        app()->setLocale('id');

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(sprintf(
            'Relasi %s::labels tidak bisa ditebak: kolom ar_labels.writer_id tidak ada dan %s tidak punya morphTo tunggal.',
            Writer::class,
            Label::class,
        ));

        Writer::find(1)->labels();
    }

    #[Test]
    public function it_leaves_an_unknown_relation_to_eloquent(): void
    {
        $this->assertNull(Writer::find(1)->unknownThing);
    }

    #[Test]
    public function it_eager_loads_dynamic_relations(): void
    {
        $writers = Writer::with('articles')->get();

        $this->assertTrue($writers->first()->relationLoaded('articles'));
        $this->assertCount(2, $writers->first()->articles);

        $articles = Article::with(['writer', 'notes'])->get();

        $this->assertSame('Ana', $articles->first()->writer->name);
        $this->assertCount(2, $articles->first()->notes);
    }

    #[Test]
    public function it_prefers_a_declared_relation(): void
    {
        $note = Note::first();

        $this->assertSame(1, $note->notable->id);
        $this->assertInstanceOf(Article::class, $note->notable);
    }

    #[Test]
    public function it_resolves_a_belongs_to_many_relation_via_a_pivot_table(): void
    {
        Schema::create('article_label', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable();
            $table->foreignId('featured_article_id')->nullable();
            $table->foreignId('label_id');
        });
        ColumnListing::flush();

        DB::table('ar_labels')->insert([['name' => 'News'], ['name' => 'Tech']]);
        DB::table('article_label')->insert([
            ['article_id' => 1, 'featured_article_id' => null, 'label_id' => 1],
            ['article_id' => 1, 'featured_article_id' => 2, 'label_id' => 2],
        ]);

        $article = Article::find(1);

        $this->assertInstanceOf(BelongsToMany::class, $article->labels());
        $this->assertSame('article_label', $article->labels()->getTable());
        $this->assertSame('article_id', $article->labels()->getForeignPivotKeyName());
        $this->assertSame('label_id', $article->labels()->getRelatedPivotKeyName());
        $this->assertSame(['News', 'Tech'], $article->labels->pluck('name')->all());
        $this->assertSame(['First'], Label::find(1)->articles->pluck('title')->all());

        $featured = Article::find(2);
        $this->assertSame('featured_article_id', $featured->featuredLabels()->getForeignPivotKeyName());
        $this->assertSame(['Tech'], $featured->featuredLabels->pluck('name')->all());

        $loaded = Article::with('labels')->find(1);
        $this->assertTrue($loaded->relationLoaded('labels'));
        $this->assertCount(2, $loaded->labels);
    }

    #[Test]
    public function it_resolves_a_belongs_to_many_relation_via_a_custom_kompact_pivot(): void
    {
        Schema::create('ar_article_label_connector', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable();
            $table->foreignId('featured_article_id')->nullable();
            $table->foreignId('label_id');
        });
        ColumnListing::flush();

        DB::table('ar_labels')->insert([['name' => 'News'], ['name' => 'Tech']]);
        DB::table('ar_article_label_connector')->insert([
            ['article_id' => 1, 'featured_article_id' => null, 'label_id' => 1],
            ['article_id' => 1, 'featured_article_id' => 2, 'label_id' => 2],
        ]);

        $article = Article::find(1);

        $this->assertInstanceOf(BelongsToMany::class, $article->labels());
        $this->assertSame('ar_article_label_connector', $article->labels()->getTable());
        $this->assertSame(ArticleLabelConnector::class, $article->labels()->getPivotClass());
        $this->assertSame('article_id', $article->labels()->getForeignPivotKeyName());
        $this->assertSame('label_id', $article->labels()->getRelatedPivotKeyName());
        $this->assertSame(['News', 'Tech'], $article->labels->pluck('name')->all());
        $this->assertInstanceOf(ArticleLabelConnector::class, $article->labels->first()->pivot);
        $this->assertSame(['First'], Label::find(1)->articles->pluck('title')->all());

        $featured = Article::find(2);
        $this->assertSame('featured_article_id', $featured->featuredLabels()->getForeignPivotKeyName());
        $this->assertSame(['Tech'], $featured->featuredLabels->pluck('name')->all());

        $loaded = Article::with('labels')->find(1);
        $this->assertTrue($loaded->relationLoaded('labels'));
        $this->assertCount(2, $loaded->labels);
    }

    protected function setUpSoftDeletedRelationTables(): void
    {
        Schema::create('ar_authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('ar_kept_authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('ar_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable();
            $table->foreignId('kept_author_id')->nullable();
            $table->string('title');
            $table->softDeletes();
        });
        Schema::create('ar_kept_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable();
            $table->string('title');
            $table->softDeletes();
        });
        Schema::create('ar_comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->string('body');
            $table->softDeletes();
        });
        Schema::create('ar_kept_comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->string('body');
            $table->softDeletes();
        });
        Schema::create('ar_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id');
            $table->foreignId('tag_id');
        });
        Schema::create('ar_kept_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('kept_tag_post', function (Blueprint $table) {
            $table->foreignId('post_id');
            $table->foreignId('kept_tag_id');
        });

        ColumnListing::flush();

        $deletedAt = '2026-01-01 00:00:00';

        DB::table('ar_authors')->insert([
            ['name' => 'Deleted Author', 'deleted_at' => $deletedAt],
            ['name' => 'Active Author', 'deleted_at' => null],
        ]);
        DB::table('ar_kept_authors')->insert([
            ['name' => 'Deleted KeptAuthor', 'deleted_at' => $deletedAt],
            ['name' => 'Active KeptAuthor', 'deleted_at' => null],
        ]);
        DB::table('ar_posts')->insert([
            ['author_id' => 1, 'kept_author_id' => 1, 'title' => 'Post With Deleted Parents', 'deleted_at' => null],
            ['author_id' => 2, 'kept_author_id' => 2, 'title' => 'Deleted Post', 'deleted_at' => $deletedAt],
            ['author_id' => 2, 'kept_author_id' => 2, 'title' => 'Active Post', 'deleted_at' => null],
        ]);
        DB::table('ar_kept_posts')->insert([
            ['author_id' => 2, 'title' => 'Deleted KeptPost', 'deleted_at' => $deletedAt],
            ['author_id' => 2, 'title' => 'Active KeptPost', 'deleted_at' => null],
        ]);
        DB::table('ar_comments')->insert([
            ['commentable_type' => Post::class, 'commentable_id' => 1, 'body' => 'Deleted Comment', 'deleted_at' => $deletedAt],
            ['commentable_type' => Post::class, 'commentable_id' => 1, 'body' => 'Active Comment', 'deleted_at' => null],
        ]);
        DB::table('ar_kept_comments')->insert([
            ['commentable_type' => Post::class, 'commentable_id' => 1, 'body' => 'Deleted KeptComment', 'deleted_at' => $deletedAt],
            ['commentable_type' => Post::class, 'commentable_id' => 1, 'body' => 'Active KeptComment', 'deleted_at' => null],
        ]);
        DB::table('ar_tags')->insert([
            ['name' => 'Deleted Tag', 'deleted_at' => $deletedAt],
            ['name' => 'Active Tag', 'deleted_at' => null],
        ]);
        DB::table('post_tag')->insert([
            ['post_id' => 1, 'tag_id' => 1],
            ['post_id' => 1, 'tag_id' => 2],
        ]);
        DB::table('ar_kept_tags')->insert([
            ['name' => 'Deleted KeptTag', 'deleted_at' => $deletedAt],
            ['name' => 'Active KeptTag', 'deleted_at' => null],
        ]);
        DB::table('kept_tag_post')->insert([
            ['post_id' => 1, 'kept_tag_id' => 1],
            ['post_id' => 1, 'kept_tag_id' => 2],
        ]);
    }

    #[Test]
    public function it_excludes_soft_deleted_related_models_by_default(): void
    {
        $this->setUpSoftDeletedRelationTables();

        $this->assertFalse(Author::includesSoftDeletedData());
        $this->assertFalse(Post::includesSoftDeletedData());
        $this->assertFalse(Comment::includesSoftDeletedData());
        $this->assertFalse(Tag::includesSoftDeletedData());

        $post = Post::find(1);
        $author = Author::find(2);

        $this->assertNull($post->author);
        $this->assertSame('Active Author', Post::find(3)->author->name);
        $this->assertSame('Active Post', $author->post->title);
        $this->assertSame(['Active Post'], $author->posts->pluck('title')->all());
        $this->assertSame(['Active Comment'], $post->comments->pluck('body')->all());
        $this->assertSame(['Active Tag'], $post->tags->pluck('name')->all());

        $eagerPost = Post::with(['author', 'comments', 'tags'])->find(1);
        $this->assertNull($eagerPost->author);
        $this->assertSame(['Active Comment'], $eagerPost->comments->pluck('body')->all());
        $this->assertSame(['Active Tag'], $eagerPost->tags->pluck('name')->all());

        $eagerAuthor = Author::with(['post', 'posts'])->find(2);
        $this->assertSame('Active Post', $eagerAuthor->post->title);
        $this->assertSame(['Active Post'], $eagerAuthor->posts->pluck('title')->all());
    }

    #[Test]
    public function it_includes_soft_deleted_related_models_when_enabled_on_target_model(): void
    {
        $this->setUpSoftDeletedRelationTables();

        $this->assertTrue(KeptAuthor::includesSoftDeletedData());
        $this->assertTrue(KeptPost::includesSoftDeletedData());
        $this->assertTrue(KeptComment::includesSoftDeletedData());
        $this->assertTrue(KeptTag::includesSoftDeletedData());

        $post = Post::find(1);
        $author = Author::find(2);

        $this->assertSame('Deleted KeptAuthor', $post->keptAuthor->name);
        $this->assertSame('Deleted KeptPost', $author->keptPost->title);
        $this->assertSame(['Deleted KeptPost', 'Active KeptPost'], $author->keptPosts->pluck('title')->all());
        $this->assertSame(['Deleted KeptComment', 'Active KeptComment'], $post->keptComments->pluck('body')->all());
        $this->assertSame(['Deleted KeptTag', 'Active KeptTag'], $post->keptTags->pluck('name')->all());

        // Target model flag controls inclusion, not caller model flag
        $this->assertSame(['Active Post'], KeptAuthor::find(2)->posts->pluck('title')->all());

        $eagerPost = Post::with(['keptAuthor', 'keptComments', 'keptTags'])->find(1);
        $this->assertTrue($eagerPost->relationLoaded('keptAuthor'));
        $this->assertSame('Deleted KeptAuthor', $eagerPost->keptAuthor->name);
        $this->assertSame(['Deleted KeptComment', 'Active KeptComment'], $eagerPost->keptComments->pluck('body')->all());
        $this->assertSame(['Deleted KeptTag', 'Active KeptTag'], $eagerPost->keptTags->pluck('name')->all());

        $eagerAuthor = Author::with(['keptPost', 'keptPosts'])->find(2);
        $this->assertTrue($eagerAuthor->relationLoaded('keptPosts'));
        $this->assertSame('Deleted KeptPost', $eagerAuthor->keptPost->title);
        $this->assertSame(['Deleted KeptPost', 'Active KeptPost'], $eagerAuthor->keptPosts->pluck('title')->all());
    }

    #[Test]
    public function it_safely_ignores_include_soft_deleted_data_when_target_does_not_use_soft_deletes(): void
    {
        Schema::create('ar_permanent_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('writer_id')->nullable();
            $table->string('name');
        });
        Schema::table('ar_articles', function (Blueprint $table) {
            $table->foreignId('permanent_badge_id')->nullable();
        });
        ColumnListing::flush();

        DB::table('ar_permanent_badges')->insert(['writer_id' => 1, 'name' => 'Founder']);
        DB::table('ar_articles')->where('id', 1)->update(['permanent_badge_id' => 1]);

        $this->assertTrue(PermanentBadge::includesSoftDeletedData());
        $this->assertFalse(Writer::find(1)->permanentBadges()->getQuery()->hasMacro('withTrashed'));

        $this->assertSame('Founder', Article::find(1)->permanentBadge->name);
        $this->assertSame('Founder', Writer::find(1)->permanentBadge->name);
        $this->assertSame(['Founder'], Writer::find(1)->permanentBadges->pluck('name')->all());

        $eagerWriter = Writer::with(['permanentBadge', 'permanentBadges'])->find(1);
        $this->assertSame('Founder', $eagerWriter->permanentBadge->name);
        $this->assertSame(['Founder'], $eagerWriter->permanentBadges->pluck('name')->all());
    }

    #[Test]
    public function it_supports_plain_models_declaring_include_soft_deleted_data_without_auto_relation(): void
    {
        Schema::create('ar_legacy_reviewers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::table('ar_articles', function (Blueprint $table) {
            $table->foreignId('legacy_reviewer_id')->nullable();
        });
        ColumnListing::flush();

        DB::table('ar_legacy_reviewers')->insert([
            'name' => 'Deleted Reviewer',
            'deleted_at' => '2026-01-01 00:00:00',
        ]);
        DB::table('ar_articles')->where('id', 1)->update(['legacy_reviewer_id' => 1]);

        $this->assertFalse(method_exists(LegacyReviewer::class, 'includesSoftDeletedData'));
        $this->assertSame('Deleted Reviewer', Article::find(1)->legacyReviewer->name);
        $this->assertSame('Deleted Reviewer', Article::with('legacyReviewer')->find(1)->legacyReviewer->name);
    }

    #[Test]
    public function it_normalizes_pivot_endpoints_across_all_supported_pivot_between_formats(): void
    {
        $this->assertSame([
            ['class' => Article::class, 'foreign_key' => null, 'relations' => ['article']],
            ['class' => Label::class, 'foreign_key' => null, 'relations' => ['label']],
        ], ArticleLabelConnector::pivotEndpoints());
        $this->assertTrue(ArticleLabelConnector::connects(Article::class, Label::class));
        $this->assertFalse(ArticleLabelConnector::connects(Article::class, Article::class));

        $expectedSelfEndpoints = [
            ['class' => ParentLedger::class, 'foreign_key' => 'casting_id', 'relations' => ['casting']],
            ['class' => ParentLedger::class, 'foreign_key' => 'target_sub_id', 'relations' => ['sub', 'targetSub']],
        ];
        $this->assertSame($expectedSelfEndpoints, CastingConnector::pivotEndpoints());
        $this->assertTrue(CastingConnector::connects(ParentLedger::class, ParentLedger::class));
        $this->assertFalse(CastingConnector::connects(ParentLedger::class, Article::class));

        $this->assertSame([
            ['class' => StemLedger::class, 'foreign_key' => 'casting_id', 'relations' => ['casting']],
            ['class' => StemLedger::class, 'foreign_key' => 'target_sub_id', 'relations' => ['sub', 'targetSub']],
        ], CastingStemConnector::pivotEndpoints());
        $this->assertTrue(CastingStemConnector::connects(StemLedger::class, StemLedger::class));

        $aliasStemPivot = new class extends KompactPivot {
            protected $table = 'ar_articles';

            protected static array $pivotBetween = [
                [ParentLedger::class, 'casting_id'],
                'sub' => [ParentLedger::class, 'target_sub'],
            ];
        };
        $this->assertSame($expectedSelfEndpoints, $aliasStemPivot::pivotEndpoints());
    }

    #[Test]
    public function it_resolves_mapped_self_pivot_relations_on_casting_connector_and_parent_ledger(): void
    {
        Schema::create('ar_parent_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->softDeletes();
        });
        Schema::create('ar_casting_connector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_id');
            $table->foreignId('target_sub_id');
            $table->softDeletes();
        });
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        DB::table('ar_parent_ledgers')->insert([
            ['code' => 'CAST-1'],
            ['code' => 'SUB-A'],
            ['code' => 'SUB-B'],
        ]);
        DB::table('ar_casting_connector')->insert([
            ['casting_id' => 1, 'target_sub_id' => 2],
        ]);

        $casting = ParentLedger::find(1);
        $subA = ParentLedger::find(2);
        $subB = ParentLedger::find(3);
        $pivot = CastingConnector::find(1);

        // BelongsTo on KompactPivot via custom foreign keys & alias
        $this->assertInstanceOf(BelongsTo::class, $pivot->casting());
        $this->assertSame('casting_id', $pivot->casting()->getForeignKeyName());
        $this->assertSame('CAST-1', $pivot->casting->code);

        $this->assertInstanceOf(BelongsTo::class, $pivot->sub());
        $this->assertSame('target_sub_id', $pivot->sub()->getForeignKeyName());
        $this->assertSame('SUB-A', $pivot->sub->code);

        $this->assertInstanceOf(BelongsTo::class, $pivot->targetSub());
        $this->assertSame('target_sub_id', $pivot->targetSub()->getForeignKeyName());
        $this->assertSame('SUB-A', $pivot->targetSub->code);

        // BelongsToMany via attach() and eager loading
        $this->assertInstanceOf(BelongsToMany::class, $casting->subs());
        $this->assertSame(CastingConnector::class, $casting->subs()->getPivotClass());
        $this->assertSame('casting_id', $casting->subs()->getForeignPivotKeyName());
        $this->assertSame('target_sub_id', $casting->subs()->getRelatedPivotKeyName());

        $casting->subs()->attach($subB->id);

        $this->assertSame(['SUB-A', 'SUB-B'], $casting->subs()->pluck('code')->all());
        $this->assertSame(['SUB-A', 'SUB-B'], $casting->targetSubs->pluck('code')->all());
        $this->assertSame(['SUB-A', 'SUB-B'], $casting->subParentLedgers->pluck('code')->all());
        $this->assertSame(['CAST-1'], $subA->castings->pluck('code')->all());

        // HasMany & HasOne onto KompactPivot
        $this->assertInstanceOf(HasMany::class, $casting->castingConnectors());
        $this->assertSame('casting_id', $casting->castingConnectors()->getForeignKeyName());
        $this->assertCount(2, $casting->castingConnectors);

        $this->assertInstanceOf(HasMany::class, $subA->subCastingConnectors());
        $this->assertSame('target_sub_id', $subA->subCastingConnectors()->getForeignKeyName());
        $this->assertCount(1, $subA->subCastingConnectors);
        $this->assertSame('target_sub_id', $subA->targetSubCastingConnectors()->getForeignKeyName());

        $this->assertInstanceOf(HasOne::class, $casting->castingConnector());
        $this->assertInstanceOf(HasOne::class, $subA->subCastingConnector());
        $this->assertSame(1, $subA->subCastingConnector->casting_id);

        $eager = ParentLedger::with(['subs', 'castingConnectors.sub'])->find(1);
        $this->assertTrue($eager->relationLoaded('subs'));
        $this->assertSame(['SUB-A', 'SUB-B'], $eager->subs->pluck('code')->all());
        $this->assertSame(['SUB-A', 'SUB-B'], $eager->castingConnectors->map(fn (CastingConnector $row) => $row->sub->code)->all());
    }

    #[Test]
    public function it_resolves_stem_mapped_self_pivot_relations(): void
    {
        Schema::create('ar_stem_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->softDeletes();
        });
        Schema::create('ar_casting_stem_connector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_id');
            $table->foreignId('target_sub_id');
            $table->softDeletes();
        });
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        DB::table('ar_stem_ledgers')->insert([
            ['code' => 'STEM-1'],
            ['code' => 'STEM-SUB-1'],
        ]);

        $casting = StemLedger::find(1);
        $sub = StemLedger::find(2);
        $casting->subs()->attach($sub->id);

        $pivot = CastingStemConnector::first();
        $this->assertSame('STEM-1', $pivot->casting->code);
        $this->assertSame('STEM-SUB-1', $pivot->sub->code);
        $this->assertSame('STEM-SUB-1', $pivot->targetSub->code);

        $this->assertSame(['STEM-SUB-1'], StemLedger::with('subs')->find(1)->subs->pluck('code')->all());
        $this->assertCount(1, $casting->castingStemConnectors);
        $this->assertCount(1, $sub->subCastingStemConnectors);
    }

    #[Test]
    public function it_resolves_non_self_custom_pivot_with_custom_foreign_keys(): void
    {
        Schema::create('ar_authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('ar_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes();
        });
        Schema::create('ar_author_post_connector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('writer_id');
            $table->foreignId('article_id');
            $table->softDeletes();
        });
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        DB::table('ar_authors')->insert([['name' => 'Rina'], ['name' => 'Seno']]);
        DB::table('ar_posts')->insert([['title' => 'Alpha'], ['title' => 'Beta']]);

        $rina = Author::find(1);
        $alpha = Post::find(1);
        $beta = Post::find(2);

        $rina->posts()->attach([$alpha->id, $beta->id]);

        $this->assertInstanceOf(BelongsToMany::class, $rina->posts());
        $this->assertSame(AuthorPostConnector::class, $rina->posts()->getPivotClass());
        $this->assertSame('writer_id', $rina->posts()->getForeignPivotKeyName());
        $this->assertSame('article_id', $rina->posts()->getRelatedPivotKeyName());
        $this->assertSame(['Alpha', 'Beta'], Author::with('posts')->find(1)->posts->pluck('title')->all());
        $this->assertSame(['Rina'], $alpha->authors->pluck('name')->all());

        // Endpoint alias plural belongsToMany even when Writer and Article models exist in namespace
        $this->assertSame(['Alpha', 'Beta'], $rina->articles->pluck('title')->all());
        $this->assertSame(['Rina'], $alpha->writers->pluck('name')->all());

        // BelongsTo on AuthorPostConnector resolves to Author/Post rather than colliding Writer/Article classes
        $connector = AuthorPostConnector::first();
        $this->assertInstanceOf(Author::class, $connector->writer);
        $this->assertSame('Rina', $connector->writer->name);
        $this->assertInstanceOf(Post::class, $connector->article);
        $this->assertSame('Alpha', $connector->article->title);

        // HasMany onto AuthorPostConnector
        $this->assertCount(2, $rina->authorPostConnectors);
        $this->assertCount(1, $alpha->authorPostConnectors);
    }
}
