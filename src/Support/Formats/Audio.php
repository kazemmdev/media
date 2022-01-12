<?php

namespace k90mirzaei\Media\Support\Formats;

use k90mirzaei\Media\Model\Media;
use k90mirzaei\Media\Model\MediaCollection;

class Audio extends MediaFormat
{
    public function upload($file, Media $media, MediaCollection $mediaCollection)
    {
        $this->media = $media;

        $this->putFileIntoStorage($this->media->getPath(), $file);
    }
}