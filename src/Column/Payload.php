<?php

namespace JundFauz\EloquentKompact\Column;

class Payload
{
    /**
     * @deprecated since 1.19.0, use ALL_NULLABLE constant instead
     */
    public const array allNullable = ALL_NULLABLE;

    /**
     * @deprecated since 1.19.0, use merge() helper instead
     * @param array ...$payloads
     * @return array
     */
    public static function build(array ...$payloads): array
    {
        return array_merge(...$payloads);
    }
}

const ALL_NULLABLE = ['all' => 'nullable'];