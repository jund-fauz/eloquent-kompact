<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use JundFauz\EloquentKompact\EloquentKompactServiceProvider;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

class MorphMapTest extends TestCase
{
    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (glob(__DIR__ . '/../Fixtures/MorphMap/*.php') as $file) {
            require_once $file;
        }

        $this->appPath = $this->app->path();
        $this->app->useAppPath(realpath(__DIR__ . '/../Fixtures'));
        Relation::morphMap([], false);
        Relation::requireMorphMap(false);
    }

    protected function tearDown(): void
    {
        $this->app->useAppPath($this->appPath);
        Relation::morphMap([], false);
        Relation::requireMorphMap(false);

        parent::tearDown();
    }

    private function configureMorphMap(string|null $use, string $namespace = 'MorphMap'): void
    {
        config(['eloquent-kompact.morph_model_use' => $use, 'eloquent-kompact.path.model' => $namespace]);

        $provider = new EloquentKompactServiceProvider($this->app);
        (new ReflectionMethod($provider, 'configureMorphMap'))->invoke($provider);
    }

    #[Test]
    public function it_maps_the_models_by_their_table(): void
    {
        $this->configureMorphMap('table');

        $this->assertSame(
            ['blog_posts' => \MorphMap\BlogPost::class, 'post_comments' => \MorphMap\Comment::class],
            Relation::morphMap(),
        );
        $this->assertTrue(Relation::requiresMorphMap());
    }

    #[Test]
    public function it_maps_the_models_by_their_class_name(): void
    {
        $this->configureMorphMap('model');

        $this->assertSame(
            ['BlogPost' => \MorphMap\BlogPost::class, 'Comment' => \MorphMap\Comment::class],
            Relation::morphMap(),
        );
    }

    #[Test]
    public function it_accepts_a_namespace_with_a_trailing_backslash(): void
    {
        $this->configureMorphMap('model', 'MorphMap\\');

        $this->assertSame(
            ['BlogPost' => \MorphMap\BlogPost::class, 'Comment' => \MorphMap\Comment::class],
            Relation::morphMap(),
        );
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function disabledProvider(): array
    {
        return ['empty' => [''], 'null' => [null]];
    }

    #[Test]
    #[DataProvider('disabledProvider')]
    public function it_does_nothing_when_disabled(?string $use): void
    {
        $this->configureMorphMap($use);

        $this->assertSame([], Relation::morphMap());
        $this->assertFalse(Relation::requiresMorphMap());
    }

    #[Test]
    public function it_rejects_an_unknown_value(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('https://github.com/jund-fauz/eloquent-kompact/issues/new');

        $this->configureMorphMap('uuid');
    }

    #[Test]
    public function it_rejects_a_missing_directory(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("doesn't exist");

        $this->configureMorphMap('table', 'MorphMap\Missing');
    }
}
