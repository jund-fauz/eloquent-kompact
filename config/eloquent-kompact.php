<?php

use function JundFauz\EloquentKompact\namespace_concat;

return [
    'column' => [
        'custom_tokens' => []
    ],
    'fillable' => [
        'exclude_column_by' => [
            'exact' => ['id'],
            'contain' => [],
            'prefix' => [],
            'postfix' => [],
            'token' => [],
            'regex' => []
        ],
        'include_explicitly' => []
    ],
    'morph_model_use' => '',
    'path' => [
        'model' => namespace_concat('App', 'Models'),
        'database_enum' => namespace_concat('App', 'Enums')
    ],
    'ide-helper' => [
        'auto_generate' => env('KOMPACT_IDE_HELPER', false)
    ],
];