<?php

use JundFauz\EloquentKompact\Regex\Regex;
use function JundFauz\EloquentKompact\namespace_concat;

return [
    'path' => [
        'model' => namespace_concat('App', 'Models'),
        'database_enum' => namespace_concat('App', 'Enums')
    ],
    'fillable' => [
        'excluded_columns_by_regex' => [
            Regex::create('id')->exact(),
            Regex::create()->endsWith('_at')
        ]
    ],
    'ide-helper' => [
        'auto_generate' => env('KOMPACT_IDE_HELPER', false)
    ]
];