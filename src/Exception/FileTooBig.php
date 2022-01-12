<?php

namespace k90mirzaei\Media\Exception;

use Exception;
use k90mirzaei\Media\Support\File;

class FileTooBig extends Exception
{
    public static function create(int $size): self
    {
        $fileSize = File::getHumanReadableSize($size);

        $maxFileSize = File::getHumanReadableSize((int)config('media.max_file_size'));

        return new static("File has a size of {$fileSize} which is greater than the maximum allowed {$maxFileSize}");
    }
}