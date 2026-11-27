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
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Article;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\ArticleLabelConnector;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Label;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Note;
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
}
