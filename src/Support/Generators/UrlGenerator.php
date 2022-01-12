<?php

namespace k90mirzaei\Media\Support\Generators;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use k90mirzaei\Media\Model\Media;

class UrlGenerator
{
    protected ?Media $media;

    public function setMedia(Media $media): self
    {
        $this->media = $media;

        return $this;
    }

    public function getUrl($path): string
    {
        return $this->getDisk()->url("{$path}");
    }

    protected function getDisk(): Filesystem
    {
        return Storage::disk($this->getDiskName());
    }

    protected function getDiskName(): string
    {
        return $this->media->disk;
    }
}