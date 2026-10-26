<?php

use JundFauz\EloquentKompact\Helper\Path;
use JundFauz\RegexBuilder\Regex;

return [
    'path' => [
        'model' => Path::namespaceConcat('App', 'Database', 'Models'),
        'database_enum' => Path::namespaceConcat('App', 'Database', 'Enums')
    ],
    'fillable' => [
        'excluded_columns_by_regex' => [
            Regex::create(Regex::exact('id')),
            Regex::create(Regex::endsWith('_at'))
        ]
    ]
];