<?php

use JundFauz\EloquentKompact\Helper\Path;
use JundFauz\RegexBuilder\Regex;

return [
    'path' => [
        'model' => Path::namespaceConcat('App', 'Models'),
        'database_enum' => Path::namespaceConcat('App', 'Enums')
    ],
    'fillable' => [
        'excluded_columns_by_regex' => [
            Regex::create('id')->exact(),
            Regex::create()->endsWith('_at')
        ]
    ]
];