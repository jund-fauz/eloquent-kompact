<?php

namespace JundFauz\EloquentKompact\Column;

use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Support\Arr;

class Config
{
    /**
     * @param array<int, ColumnDefinition|ForeignKeyDefinition> $columns
     * @param array<string, string|array> $payloads
     * @return void
     */
    public static function apply(array $columns, array $payloads): void
    {
        if ($toAllPayload = Arr::pull($payloads, 'all')) {
            foreach ($columns as $column) {
                self::applies($column, $toAllPayload);
            }
        }
        $columnsByName = [];
        $processedColumns = [];
        foreach ($columns as $column) {
            if ($payload = Arr::pull($payloads, $column->name)) {
                self::applies($column, $payload);
                $processedColumns[] = $column->name;
            }
            $columnsByName[$column->name] = $column;
        }
        $columnNames = array_keys($columnsByName);
        if (! empty($payloads)) {
            $restPayloads = Arr::pull($payloads, 'rest');
            foreach ($payloads as $regex => $payload) {
                if (@preg_match($regex, '') !== false) {
                    $matchColumnNames = preg_grep($regex, $columnNames);
                    foreach ($matchColumnNames as $columnName) {
                        self::applies($columnsByName[$columnName], $payload);
                        $processedColumns[] = $columnName;
                    }
                }
            }
            if ($restPayloads) {
                foreach (array_filter($columns, fn(ColumnDefinition|ForeignKeyDefinition $column) => ! in_array($column->name, $processedColumns)) as $column) {
                    self::applies($column, $restPayloads);
                }
            }
        }
    }

    public static function applies(ColumnDefinition|ForeignKeyDefinition $column, mixed $payloads): ColumnDefinition|ForeignKeyDefinition
    {
        if (! is_array($payloads)) {
            $payloads = Arr::wrap($payloads);
        }
        foreach ($payloads as $payload => $parameters) {
            if (is_numeric($payload)) {
                $column->{$parameters}();
            } else {
                $parameters = Arr::wrap($parameters);
                $column->{$payload}(...$parameters);
            }
        }

        return $column;
    }
}
