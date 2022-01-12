<?php

namespace k90mirzaei\Media\Model;

class MediaCollection
{
    public string $name = '';

    public string $diskName = '';

    public int $maxWidth = 0;

    public int $maxFileSize = 0;

    public array $acceptsMimeTypes = [];

    public bool $generateResponsiveImages = false;

    public $collectionSizeLimit = false;

    public bool $singleFile = false;

    public string $fallbackUrl = '';

    public function __construct(string $name)
    {
        $this->name = $name;

        $this->mediaConversionRegistrations = function () {
        };

        $this->acceptsFile = fn() => true;
    }

    public static function create($name)
    {
        return new static($name);
    }

    public function acceptsMimeTypes(array $mimeTypes): self
    {
        $this->acceptsMimeTypes = $mimeTypes;

        return $this;
    }

    public function useDisk(string $diskName): self
    {
        $this->diskName = $diskName;

        return $this;
    }

    public function withResponsiveImages(): self
    {
        $this->generateResponsiveImages = true;

        return $this;
    }

    public function withMaxWidth(int $max): self
    {
        $this->maxWidth = $max;

        return $this;
    }

    public function withMaxSize(int $size): self
    {
        $this->maxFileSize = $size;

        return $this;
    }

    public function singleFile(): self
    {
        return $this->onlyKeepLatest(1);
    }

    public function onlyKeepLatest(int $maximumNumberOfItemsInCollection): self
    {
        if ($maximumNumberOfItemsInCollection < 1) {
            throw new \Exception("You should pass a value higher than 0. `{$maximumNumberOfItemsInCollection}` given.");
        }

        $this->singleFile = ($maximumNumberOfItemsInCollection === 1);

        $this->collectionSizeLimit = $maximumNumberOfItemsInCollection;

        return $this;
    }

    public function useFallbackUrl(string $url): self
    {
        $this->fallbackUrl = $url;

        return $this;
    }
}