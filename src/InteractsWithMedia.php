<?php

namespace k90mirzaei\Media;

use Illuminate\Support\Facades\Storage;
use k90mirzaei\Media\Model\Media;
use k90mirzaei\Media\Model\MediaCollection;
use k90mirzaei\Media\Support\FileAdder;
use k90mirzaei\Media\Support\FileAdderFactory;

trait InteractsWithMedia
{
    public array $mediaCollections = [];

    protected bool $deletePreservingMedia = true;

    protected array $unAttachedMediaLibraryItems = [];

    public function addMedia($file): FileAdder
    {
        return app(FileAdderFactory::class)->create($this, $file);
    }

    public function addMediaCollection(string $name): MediaCollection
    {
        $mediaCollection = MediaCollection::create($name);

        $this->mediaCollections[] = $mediaCollection;

        return $mediaCollection;
    }

    public function registerMediaCollections(): void
    {

    }

    public function Thumb(string $collectionName = 'thumb')
    {
        return $this->morphMany(Media::class, 'model')->where('collection_name', $collectionName);
    }

    public function Media(string $collectionName = 'default')
    {
        return $this->morphMany(Media::class, 'model')->where('collection_name', $collectionName);
    }

    public function getMedia(string $collectionName = 'default')
    {
        return $this->Media($collectionName)->get();
    }

    public function getFirstMedia(string $collectionName = 'default'): ?Media
    {
        $media = $this->Media($collectionName);

        return $media->first();
    }

    public function getFirstMediaUrl(string $collectionName = 'default'): string
    {
        $media = $this->getFirstMedia($collectionName);

        if (!$media) {
            return $this->getFallbackMediaUrl($collectionName) ?: '';
        }

        return $media->getUrl();
    }

    public function clearMediaCollectionExcept(string $collectionName = 'default', $excludedMedia = [])
    {
        if ($excludedMedia instanceof Media) {
            $excludedMedia = collect()->push($excludedMedia);
        }

        $excludedMedia = collect($excludedMedia);

        if (!$excludedMedia->isEmpty()) {
            $this->getMedia($collectionName)
                ->reject(fn(Media $media) => $excludedMedia->where($media->getKeyName(), $media->getKey())->count())
                ->each(fn(Media $media) => Storage::disk($media->disk)->deleteDirectory('' . $media->id))
                ->each(fn(Media $media) => $media->delete());
        }
    }

    public function getMediaCollection(string $collectionName = 'default'): ?MediaCollection
    {
        $this->registerMediaCollections();

        return collect($this->mediaCollections)
            ->first(fn (MediaCollection $collection) => $collection->name === $collectionName);
    }

    public function getFallbackMediaUrl(string $collectionName = 'default'): string
    {
        return $this->getMediaCollection($collectionName)->fallbackUrl ?? '';
    }
}
