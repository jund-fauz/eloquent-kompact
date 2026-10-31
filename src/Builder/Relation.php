<?php

namespace JundFauz\EloquentKompact\Builder;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Grammar;
use JundFauz\EloquentKompact\Internal\Concerns\Builder;
use JundFauz\EloquentKompact\Relation\Arrange;
use function JundFauz\EloquentKompact\relation_concat;

class Relation extends Builder implements Expression
{
    private string $column = '';

    public static function arrange(string $column = ''): static
    {
        $relation = new static;
        $relation->column = $column;

        return $relation;
    }

    /**
     * @param class-string<Model> ...$modelClasses
     * @return static
     */
    public function relation(string ...$modelClasses): static
    {
        return $this->append(Arrange::relation('', ...$modelClasses));
    }

    /**
     * @param class-string<Model> ...$modelClasses
     * @return static
     */
    public function relations(string ...$modelClasses): static
    {
        return $this->append(Arrange::relations('', ...$modelClasses));
    }

    private function append(string $path): static
    {
        return $this->set(relation_concat(array_filter([$this->result, $path], 'strlen')));
    }

    protected function set(string $newResult): static
    {
        $relation = parent::set($newResult);
        $relation->column = $this->column;

        return $relation;
    }

    public function build(): string
    {
        return relation_concat(array_filter([$this->result, $this->column], 'strlen'));
    }

    public function getValue(Grammar $grammar): string
    {
        return $this->build();
    }
}