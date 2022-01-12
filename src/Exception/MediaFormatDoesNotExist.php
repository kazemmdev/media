<?php

namespace k90mirzaei\Media\Exception;

use Exception;

class MediaFormatDoesNotExist extends Exception
{
    public static function create($format): self
    {
        return new static("Media format {$format} does\'nt defined!");
    }
}
