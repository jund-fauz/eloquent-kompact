<?php

namespace JundFauz\EloquentKompact\Console;

use Illuminate\Console\Command;
use JundFauz\EloquentKompact\Math\Math;
use function JundFauz\EloquentKompact\add_php_extension;
use function JundFauz\EloquentKompact\add_tab;
use function JundFauz\EloquentKompact\Array\merge;
use function JundFauz\EloquentKompact\build_class;
use function JundFauz\EloquentKompact\build_class_docblock;
use function JundFauz\EloquentKompact\create_php_content;
use function JundFauz\EloquentKompact\ensure_directory_exists;
use function JundFauz\EloquentKompact\full_path_on_app;
use function JundFauz\EloquentKompact\is_file_exist;
use function JundFauz\EloquentKompact\namespace_trim;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\write_file;
use function JundFauz\EloquentKompact\write_line;
use const JundFauz\EloquentKompact\CLASS_CLOSER;
use const JundFauz\EloquentKompact\CLASS_OPENER;

class MathCommand extends Command
{
    /** New Signature Style */
    protected $signature = 'make:kompact-math
        {--c|class= : Name of the class (asked when not given)}
        {--p|precision= : Decimal precision that will be applied to created class} 
        {--o|output= : Namespace of the classes inside app/, e.g. App\Logic}';

    protected $description = 'Generate a child class based on Math class with user-defined precision for precision math operation';

    public function handle(): void
    {
        $className = $this->option('class') ?: $this->ask('Name of the class');
        $filename = add_php_extension($className);
        $precision = $this->option('precision') ?: $this->ask('Decimal precision that will be applied to created class') ?? config('eloquent-kompact.decimal.default-precision');
        $namespace = namespace_trim($this->option('output') ?: $this->ask('Namespace of the classes inside app/', config('eloquent-kompact.path.math')));
        $directory = full_path_on_app($namespace);
        $sameWithParent = same_with($className, 'Math');

        ensure_directory_exists($directory);

        if (is_file_exist($directory, $filename)) {
            $this->line("$filename existed in directory $directory. Command aborted.");

            return;
        }

        $docblock = build_class_docblock(
            merge(
                array_fill_keys(['plus', 'minus', 'multiply', 'divide'], 'string'),
                array_fill_keys(['higherThan', 'lowerThan', 'equalTo', 'higherThanOrEqualTo', 'lowerThanOrEqualTo'], 'bool')
            ),
            '',
            true,
            'float|int|string $firstNumber, float|int|string $secondNumber'
        );

        $classContent = build_class(
            $className,
            write_line(
                CLASS_OPENER,
                add_tab("protected static ?int \$precision = $precision;"),
                CLASS_CLOSER
            ),
            $sameWithParent ? 'BaseMath' : 'Math',
            $docblock
        );

        $fileContent = create_php_content(
            $namespace,
            $sameWithParent ? [Math::class => 'BaseMath'] : Math::class,
            $classContent
        );

        write_file($directory, $filename, $fileContent);
    }
}