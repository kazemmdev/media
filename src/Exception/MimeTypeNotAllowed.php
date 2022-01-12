<?php

namespace k90mirzaei\Media\Exception;

use Exception;

class MimeTypeNotAllowed extends Exception
{
    public static function create($fileExtension, array $allowedMimeTypes): self
    {
        $allowedMimeTypes = implode(', ', $allowedMimeTypes);

        return new static("File has a mime type of {$fileExtension}, while only {$allowedMimeTypes} are allowed");
    }
}
