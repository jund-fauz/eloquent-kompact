<?php

namespace JundFauz\EloquentKompact;

use Illuminate\Support\Facades\File;
use JundFauz\EloquentKompact\Builder\Str;
use function JundFauz\EloquentKompact\Array\flat;
use function JundFauz\EloquentKompact\Array\merge;
use function JundFauz\EloquentKompact\Array\map;
use function JundFauz\EloquentKompact\Array\wrap;
use function JundFauz\EloquentKompact\String\concat;
use function JundFauz\EloquentKompact\String\rtrim_multiline;

const CLASS_OPENER = "\n{";
const CLASS_CLOSER = "}";

const DOCBLOCK_OPENER = '/**';
const DOCBLOCK_CLOSER = " */\n";

/**
 * Writes the contents to the file in the directory, replacing the file when it exists
 *
 * @param string $directory
 * @param string $filename
 * @param string $contents
 * @return void
 */
function write_file(string $directory, string $filename, string $contents): void
{
    File::put(
        path_concat($directory, $filename),
        $contents
    );
}

/**
 * Whether the file exists in the directory
 *
 * @param string $directory
 * @param string $filename
 * @return bool
 */
function is_file_exist(string $directory, string $filename): bool
{
    return File::exists(path_concat($directory, $filename));
}

/**
 * Creates the directory, its parents included, when it does not exist
 *
 * @param string $directory
 * @return void
 */
function ensure_directory_exists(string $directory): void
{
    File::ensureDirectoryExists($directory);
}

/**
 * The content of a PHP file: the open tag, the namespace, the imports and the class
 *
 * @param string $namespace
 * @param array|string $use A class to import, or a list of them; a class => alias item is imported with the alias
 * @param string $classContent
 * @return string
 */
function create_php_content(string $namespace, array|string $use, string $classContent): string
{
    return Str::of('<?php')
        ->addAtNewLine(syntax("namespace $namespace"), 2)
        ->addAtNewLine(write_line(map(fn ($use, $key) => syntax(word_concat('use', is_string($key) ? "$key as $use" : $use)), wrap($use))), 2)
        ->addAtNewLine($classContent, 2)
        ->value();
}

/**
 * The statement ended with ';'
 *
 * @param string $syntax
 * @return string
 */
function syntax(string $syntax): string
{
    return concat($syntax, ';');
}

/**
 * The declaration of a class, with its docblock and its parent class when they are given.
 * The spaces at the end of every line are trimmed
 *
 * @param string $className
 * @param string $classContent The body of the class, '{}' when empty
 * @param string $parent
 * @param string $docblock
 * @return string
 */
function build_class(string $className, string $classContent = '{}', string $parent = '', string $docblock = ''): string
{
    $arguments = [concat($docblock ?: '', 'class'), $className];
    if ($parent) {
        $arguments = merge($arguments, ['extends', $parent]);
    }
    $arguments[] = $classContent;
    return rtrim_multiline(word_concat($arguments), ' ');
}

/**
 * A class docblock with a @method line for every method
 *
 * @param array $methods Method names, or method name => return type
 * @param string $returnType The return type of the methods without one
 * @param bool $isStatic Whether the methods are static
 * @param string $functionParameters The parameters of every method, with or without the parentheses
 * @param string $docblockPrefix A description above the @method lines
 * @return string
 */
function build_class_docblock(array $methods, string $returnType = 'mixed', bool $isStatic = false, string $functionParameters = '', string $docblockPrefix = ''): string
{
    $methodDocArguments = ['@method'];
    if ($isStatic) {
        $methodDocArguments[] = 'static';
    }

    $docblockArguments = [];
    if ($docblockPrefix) {
        $docblockArguments[] = comment_doc($docblockPrefix);
    }
    $docblockArguments[] = comment_doc();

    $strParameter = Str::of($functionParameters, true);
    if ($functionParameters) {
        if (not($strParameter->startsWith('('))) {
            $strParameter->addAtStart('(');
        }

        if (not($strParameter->endsWith(')'))) {
            $strParameter->add(')');
        }
    }
    $functionParameters = $strParameter->value();

    return write_line(
        DOCBLOCK_OPENER,
        $docblockArguments,
        map(fn (string $method, $key) => comment_doc(Str::of(is_string($key) ? $key : $method)->add($functionParameters)->addWordAtStart($methodDocArguments, is_string($key) ? $method : $returnType)->value()), $methods),
        DOCBLOCK_CLOSER
    );
}

/**
 * The lines joined with a new line, nested arrays flattened
 *
 * @param string|array ...$lines
 * @return string
 */
function write_line(string|array ...$lines): string
{
    flat($lines);
    return join(NEW_LINE, $lines);
}

/**
 * A single line comment, e.g. '// sentence'
 *
 * @param string $sentence
 * @return string
 */
function comment(string $sentence): string
{
    return word_concat('//', $sentence);
}

/**
 * A line of a docblock, e.g. ' * sentence'
 *
 * @param string $sentence
 * @return string
 */
function comment_doc(string $sentence = ''): string
{
    return word_concat(' *', $sentence);
}

/**
 * The sentence indented with the TAB constant $count times; nothing for a count below 1
 *
 * @param string $sentence
 * @param int $count
 * @return string
 */
function add_tab(string $sentence, int $count = 1): string
{
    return concat(str_repeat(TAB, max($count, 0)), $sentence);
}
