<?php

namespace k90mirzaei\Media;

use k90mirzaei\Media\Model\Media;
use k90mirzaei\Media\Model\MediaCollection;
use k90mirzaei\Media\Support\FileAdder;

interface HasMedia
{
    public function addMedia($file): FileAdder;

    public function addMediaCollection(string $name): MediaCollection;

    public function registerMediaCollections(): void;

    public function getMedia(string $collectionName = 'default');

    public function getFirstMedia(string $collectionName = 'default'): ?Media;

    public function getFirstMediaUrl(string $collectionName = 'default'): string;

    public function clearMediaCollectionExcept(string $collectionName = 'default', $excludedMedia = []);

    public function getMediaCollection(string $collectionName = 'default'): ?MediaCollection;

    public function getFallbackMediaUrl(string $collectionName = 'default'): string;
}