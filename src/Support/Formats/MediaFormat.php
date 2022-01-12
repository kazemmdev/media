<?php

namespace k90mirzaei\Media\Support\Formats;

use Illuminate\Support\Facades\Storage;
use k90mirzaei\Media\Model\Media;
use k90mirzaei\Media\Model\MediaCollection;

abstract class MediaFormat
{
    protected $file;

    protected Media $media;

    protected function putFileIntoStorage(string $path, $file)
    {
        Storage::disk($this->media->disk)->put($path, $file->__toString());
    }

    abstract public function upload($file, Media $media, MediaCollection $mediaCollection);
}