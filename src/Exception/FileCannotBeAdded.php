<?php

namespace k90mirzaei\Media\Exception;

use Exception;

class FileCannotBeAdded extends Exception
{
    public static function create(): self
    {
        return new static('Only strings, FileObjects and UploadedFileObjects can be imported');
    }
}