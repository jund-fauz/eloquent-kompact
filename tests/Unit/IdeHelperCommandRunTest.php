<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Console\IdeHelperCommand;
use JundFauz\EloquentKompact\Model\Builder;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Article;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Label;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Note;
use JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation\Writer;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IdeHelperCommandRunTest extends TestCase
{
    private const string Namespace = 'JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation';
    private const string MixinNamespace = 'EloquentKompact\IdeHelper\JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation';

    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();

        // The command finds the models by their files in app_path(), so the fixture
        // models get a file there; the classes themselves come from the autoloader.
        $this->appPath = sys_get_temp_dir().'/eloquent-kompact-ide-helper-'.uniqid();
        $modelPath = $this->appPath.'/JundFauz/EloquentKompact/Tests/Fixtures/AutoRelation';
        File::ensureDirectoryExists($modelPath);

        foreach ([Writer::class, Article::class, Note::class, Label::class] as $class) {
            File::put($modelPath.'/'.class_basename($class).'.php', '<?php');
        }

        File::put($modelPath.'/NotAModel.php', '<?php');

        $this->app->useAppPath($this->appPath);
        config(['eloquent-kompact.path.model' => self::Namespace]);
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        Schema::create('ar_writers', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('ar_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('writer_id');
            $table->foreignId('editor_writer_id');
        });
        Schema::create('ar_notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');
        });
        Schema::create('ar_labels', function (Blueprint $table) {
            $table->id();
        });

        $this->removeOutput();
    }

    protected function tearDown(): void
    {
        $this->removeOutput();
        File::deleteDirectory($this->appPath);
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        parent::tearDown();
    }

    private function removeOutput(): void
    {
        File::delete([base_path(IdeHelperCommand::Filename), base_path('ide.json')]);
    }

    private function helperFile(): string
    {
        return File::get(base_path(IdeHelperCommand::Filename));
    }

    private function ideJson(): array
    {
        return json_decode(File::get(base_path('ide.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    #[Test]
    public function it_writes_the_helper_file_and_ide_json(): void
    {
        $this->artisan('kompact:ide-helper')
            ->expectsOutput('4 model(s) written to _ide_helper_kompact.php and ide.json.')
            ->assertSuccessful();

        $this->assertFileExists(base_path(IdeHelperCommand::Filename));
        $this->assertFileExists(base_path('ide.json'));
        $this->assertStringStartsWith('<?php', $this->helperFile());
    }

    #[Test]
    public function it_writes_the_dynamic_relations_of_each_model(): void
    {
        $this->artisan('kompact:ide-helper')->assertSuccessful();

        $file = $this->helperFile();
        $writer = '\\'.Writer::class;
        $article = '\\'.Article::class;
        $note = '\\'.Note::class;
        $builder = '\\'.Builder::class;

        $this->assertStringContainsString('namespace '.self::MixinNamespace.' {', $file);
        $this->assertStringContainsString('class _EK_Writer {}', $file);
        $this->assertStringContainsString('class _EK_Article {}', $file);
        $this->assertStringNotContainsString('_EK_NotAModel', $file);

        // hasMany, also with a prefix
        $this->assertStringContainsString("@property-read \\Illuminate\\Database\\Eloquent\\Collection<int, $article> \$articles", $file);
        $this->assertStringContainsString("@property-read \\Illuminate\\Database\\Eloquent\\Collection<int, $article> \$editorArticles", $file);
        $this->assertStringContainsString("@method \\Illuminate\\Database\\Eloquent\\Relations\\HasMany<$article, $writer>|$builder articles()", $file);

        // belongsTo, also with a prefix
        $this->assertStringContainsString("@property-read $writer|null \$writer", $file);
        $this->assertStringContainsString("@property-read $writer|null \$editorWriter", $file);
        $this->assertStringContainsString("@method \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo<$writer, $article>|$builder writer()", $file);

        // morphMany, which is accepted on every model without a morph map
        $this->assertStringContainsString("@method \\Illuminate\\Database\\Eloquent\\Relations\\MorphMany<$note, $article>|$builder notes()", $file);
        $this->assertStringContainsString("@method \\Illuminate\\Database\\Eloquent\\Relations\\MorphMany<$note, \\".Label::class.">|$builder notes()", $file);
    }

    #[Test]
    public function it_limits_morph_many_to_the_morph_map(): void
    {
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap(['article' => Article::class]);

        try {
            $this->artisan('kompact:ide-helper')
                ->expectsOutput('2 model(s) written to _ide_helper_kompact.php and ide.json.')
                ->assertSuccessful();
        } finally {
            \Illuminate\Database\Eloquent\Relations\Relation::morphMap([], false);
        }

        $file = $this->helperFile();

        $this->assertStringContainsString('class _EK_Article {}', $file);
        $this->assertStringNotContainsString('class _EK_Label {}', $file);
        $this->assertStringNotContainsString('MorphMany<\\'.Note::class.', \\'.Writer::class.'>', $file);
    }

    #[Test]
    public function it_skips_a_relation_declared_as_a_method(): void
    {
        $this->artisan('kompact:ide-helper')->assertSuccessful();

        $this->assertStringNotContainsString('$notable', $this->helperFile());
    }

    #[Test]
    public function it_warns_about_a_relation_that_cannot_be_resolved(): void
    {
        $this->artisan('kompact:ide-helper')
            ->expectsOutputToContain(Writer::class.'::labels')
            ->assertSuccessful();

        $this->assertStringNotContainsString('$labels', $this->helperFile());
    }

    #[Test]
    public function it_registers_the_mixins_in_ide_json(): void
    {
        $this->artisan('kompact:ide-helper')->assertSuccessful();

        $json = $this->ideJson();

        $this->assertSame('https://laravel-ide.com/schema/laravel-ide-v2.json', $json['$schema']);
        $this->assertContains(
            ['classFqn' => '\\'.Writer::class, 'mixinFqn' => '\\'.self::MixinNamespace.'\\_EK_Writer'],
            $json['helperCode']['classMixins'],
        );
        $this->assertCount(4, $json['helperCode']['classMixins']);
    }

    #[Test]
    public function it_keeps_other_mixins_and_replaces_its_own_in_ide_json(): void
    {
        $other = ['classFqn' => '\App\Models\User', 'mixinFqn' => '\Other\UserMixin'];
        $old = ['classFqn' => '\App\Models\Old', 'mixinFqn' => '\EloquentKompact\IdeHelper\App\Models\_EK_Old'];

        File::put(base_path('ide.json'), json_encode([
            '$schema' => 'custom',
            'other' => true,
            'helperCode' => ['classMixins' => [$other, $old]],
        ]));

        $this->artisan('kompact:ide-helper')->assertSuccessful();

        $json = $this->ideJson();
        $mixins = $json['helperCode']['classMixins'];

        $this->assertSame('custom', $json['$schema']);
        $this->assertTrue($json['other']);
        $this->assertSame($other, $mixins[0]);
        $this->assertNotContains($old, $mixins);
        $this->assertCount(5, $mixins);
    }

    #[Test]
    public function it_writes_an_empty_helper_without_models(): void
    {
        File::cleanDirectory($this->appPath);

        $this->artisan('kompact:ide-helper')
            ->expectsOutput('0 model(s) written to _ide_helper_kompact.php and ide.json.')
            ->assertSuccessful();

        $this->assertStringNotContainsString('namespace', $this->helperFile());
        $this->assertSame([], $this->ideJson()['helperCode']['classMixins']);
    }
}
