<?php

namespace JundFauz\EloquentKompact\Builder;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Grammar;
use JundFauz\EloquentKompact\Internal\Concerns\Builder;
use JundFauz\EloquentKompact\Relation\Arrange;
use function JundFauz\EloquentKompact\relation_concat;

/**
 * Fluent builder for Eloquent dot-notated relation paths and column expressions.
 *
 * @final
 */
class Relation extends Builder implements Expression
{
    private string $column = '';

    /**
     * Create a new relation path builder, optionally targeting a leaf column.
     *
     * @param string $column
     * @return static
     */
    public static function arrange(string $column = ''): static
    {
        $relation = new static;
        $relation->column = $column;

        return $relation;
    }

    /**
     * Append singular relation segment(s) resolved from model class names or prefixed maps.
     *
     * @param class-string<Model>|array<string, class-string<Model>> ...$modelClasses
     * @return static
     */
    public function relation(string|array ...$modelClasses): static
    {
        return $this->append(Arrange::relation('', ...$modelClasses));
    }

    /**
     * Append plural relation segment(s) resolved from model class names or prefixed maps.
     *
     * @param class-string<Model>|array<string, class-string<Model>> ...$modelClasses
     * @return static
     */
    public function relations(string|array ...$modelClasses): static
    {
        return $this->append(Arrange::relations('', ...$modelClasses));
    }

    /**
     * Append a relation path segment to the current accumulated path.
     *
     * @param string $path
     * @return static
     */
    private function append(string $path): static
    {
        return $this->set(relation_concat(array_filter([$this->result, $path], 'strlen')));
    }

    /**
     * Create a new builder instance with the updated relation path while preserving the column.
     *
     * @param string $newResult
     * @return static
     */
    protected function set(string $newResult): static
    {
        $relation = parent::set($newResult);
        $relation->column = $this->column;

        return $relation;
    }

    /**
     * Build the final dot-notated relation path string.
     *
     * @return string
     */
    public function build(): string
    {
        return relation_concat(array_filter([$this->result, $this->column], 'strlen'));
    }

    /**
     * Get the value of the expression for the database grammar.
     *
     * @param Grammar $grammar
     * @return string
     */
    public function getValue(Grammar $grammar): string
    {
        return $this->build();
    }
}