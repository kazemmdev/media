<?php

namespace k90mirzaei\Media\Exception;

use Exception;

class MimeTypeNotDefined extends Exception
{
    public static function create($fileExtension): self
    {
        return new static("File has a mime type of {$fileExtension}, while not defined in package");
    }
}
