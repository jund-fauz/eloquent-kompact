<?php

namespace JundFauz\EloquentKompact\Internal\Helper;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use UnitEnum;

/**
 * @internal
 */
final class Caster extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Casts `$value` with `$cast`, normalizing non-raw values via `setAttribute()` when needed.
     *
     * @param mixed $value
     * @param string $cast
     * @return mixed
     */
    public function castValue(mixed $value, string $cast): mixed
    {
        if ($value instanceof Stringable) {
            $value = $value->value();
        }

        $model = (clone $this)->mergeCasts(['value' => $cast]);
        $isEnumCast = $model->isEnumCastable('value');
        $isClassCast = ! $isEnumCast && $model->isClassCastable('value');

        if ($value instanceof BackedEnum && ! $isEnumCast && ! $isClassCast) {
            $value = $value->value;
        }

        if (
            is_array($value)
            || (
                is_object($value)
                && ! $value instanceof UnitEnum
                && ! ($value instanceof DateTimeInterface && ($model->isDateCastable('value') || $model->hasCast('value', 'timestamp')))
            )
            || (
                ! is_null($value)
                && ($cast === 'hashed' || ($isClassCast && $model->resolveCasterClass('value') instanceof CastsInboundAttributes))
            )
        ) {
            $model->setAttribute('value', $value);

            return $model->setRawAttributes($model->getAttributes())->value;
        }

        return $model->setRawAttributes(['value' => $value])->value;
    }
}