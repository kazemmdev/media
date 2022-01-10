<?php

namespace k90mirzaei\Media\Exception;

use Exception;
use k90mirzaei\Media\Support\File;

class UnknownType extends Exception
{
    public static function create(): self
    {
        return new static('Only strings, FileObjects and UploadedFileObjects can be imported');
    }
}