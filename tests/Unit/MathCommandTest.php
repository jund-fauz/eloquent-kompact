<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Support\Facades\File;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MathCommandTest extends TestCase
{
    private string $appPath;
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appPath = $this->app->path();
        $this->tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kompact-math-' . uniqid();
        $this->app->useAppPath($this->tempPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempPath);
        $this->app->useAppPath($this->appPath);

        parent::tearDown();
    }

    private function file(string $path): string
    {
        return File::get($this->tempPath . '/' . $path);
    }

    #[Test]
    public function it_generates_a_child_class_of_math_with_the_precision(): void
    {
        $this->artisan('make:kompact-math', ['--class' => 'Money', '--precision' => '2', '--output' => 'App\Logic'])->assertSuccessful();

        $content = $this->file('Logic/Money.php');

        $this->assertStringStartsWith("<?php\n\nnamespace App\Logic;\n\nuse JundFauz\EloquentKompact\Math\Math;\n\n", $content);
        $this->assertStringContainsString('class Money extends Math', $content);
        $this->assertStringContainsString('protected static ?int $precision = 2;', $content);
        $this->assertStringContainsString(' * @method static string plus(float|int|string $firstNumber, float|int|string $secondNumber)', $content);
        $this->assertStringContainsString(' * @method static bool lowerThanOrEqualTo(float|int|string $firstNumber, float|int|string $secondNumber)', $content);
    }

    #[Test]
    public function the_generated_class_calculates_with_its_precision(): void
    {
        $this->artisan('make:kompact-math', ['--class' => 'MathCommandTestMoney', '--precision' => '2', '--output' => 'App\Logic'])->assertSuccessful();

        require $this->tempPath . '/Logic/MathCommandTestMoney.php';

        $this->assertSame('2.55', \App\Logic\MathCommandTestMoney::plus('1.555', '1'));
    }

    #[Test]
    public function it_uses_the_precision_of_the_config_by_default(): void
    {
        config(['eloquent-kompact.decimal.default-precision' => 4]);

        $this->artisan('make:kompact-math', ['--class' => 'Money', '--output' => 'App\Logic'])
            ->expectsQuestion('Decimal precision that will be applied to created class', null)
            ->assertSuccessful();

        $this->assertStringContainsString('protected static ?int $precision = 4;', $this->file('Logic/Money.php'));
    }

    #[Test]
    public function it_imports_math_with_an_alias_when_the_class_is_named_math(): void
    {
        $this->artisan('make:kompact-math', ['--class' => 'Math', '--precision' => '2', '--output' => 'App\Logic'])->assertSuccessful();

        $content = $this->file('Logic/Math.php');

        $this->assertStringContainsString('use JundFauz\EloquentKompact\Math\Math as BaseMath;', $content);
        $this->assertStringContainsString('class Math extends BaseMath', $content);
    }

    #[Test]
    public function it_keeps_an_existing_class(): void
    {
        File::ensureDirectoryExists($this->tempPath . '/Logic');
        File::put($this->tempPath . '/Logic/Money.php', 'existing');

        $this->artisan('make:kompact-math', ['--class' => 'Money', '--precision' => '2', '--output' => 'App\Logic'])
            ->expectsOutputToContain('Command aborted.')
            ->assertSuccessful();

        $this->assertSame('existing', $this->file('Logic/Money.php'));
    }
}
