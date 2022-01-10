<?php

namespace k90mirzaei\Media\Exception;

use Exception;

class DiskDoesNotExist extends Exception
{
    public static function create(string $diskName): self
    {
        return new static("There is no filesystem disk named `{$diskName}`");
    }
}
