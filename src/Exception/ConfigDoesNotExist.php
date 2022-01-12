<?php

namespace k90mirzaei\Media\Exception;

use Exception;

class ConfigDoesNotExist extends Exception
{
    public static function create(): self
    {
        return new static('Please publish the config file by running ' .
            '\'php artisan vendor:publish --tag=media-config\'');
    }
}
