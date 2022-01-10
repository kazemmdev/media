<?php

namespace k90mirzaei\Media\Exception;

use Exception;
use k90mirzaei\Media\Support\File;

class FileTooBig extends Exception
{
    public static function create(string $path, int $size = null): self
    {
        $fileSize = File::getHumanReadableSize($size ?: filesize($path));

        $maxFileSize = File::getHumanReadableSize(config('media-library.max_file_size'));

        return new static("File `{$path}` has a size of {$fileSize} which is greater than the maximum allowed {$maxFileSize}");
    }
}