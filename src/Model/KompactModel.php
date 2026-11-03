<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Model;
use JundFauz\EloquentKompact\Model\Traits\Kompact;

abstract class KompactModel extends Model
{
    use Kompact;
}
