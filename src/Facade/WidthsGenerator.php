<?php

namespace k90mirzaei\Media\Facade;

use Illuminate\Support\Facades\Facade;

class WidthsGenerator extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'WidthsGenerator';
    }
}