<?php

use JundFauz\RegexBuilder\Regex;

return [
    'path' => [
        'model' => "App\Database\Models",
        'database_enum' => "App\Database\Enum"
    ],
    'fillable' => [
        'excluded_columns_by_regex' => [
            Regex::create(Regex::exact('id')),
            Regex::create(Regex::endsWith('_at'))
        ]
    ]
];