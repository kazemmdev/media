<?php

namespace k90mirzaei\Media\Exception;

use Exception;

class MediaDimensionInfeasible extends Exception
{
    public static function create(): self
    {
        return new static('the package cannot calculate dimension of this media');
    }
}