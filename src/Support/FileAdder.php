<?php

namespace k90mirzaei\Media\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Arr;
use k90mirzaei\Media\Exception\ConfigDoesNotExist;
use k90mirzaei\Media\Exception\DiskDoesNotExist;
use k90mirzaei\Media\Exception\FileTooBig;
use k90mirzaei\Media\Exception\MediaFormatDoesNotExist;
use k90mirzaei\Media\Exception\MimeTypeNotAllowed;
use k90mirzaei\Media\Exception\MimeTypeNotDefined;
use k90mirzaei\Media\Exception\UnknownType;
use k90mirzaei\Media\Model\Media;
use k90mirzaei\Media\Model\MediaCollection;
use k90mirzaei\Media\Support\Formats\MediaFormat;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileAdder
{
    protected $file;

    protected ?Model $subject;

    protected int $fileSize;

    protected string $fileName;

    protected string $mimeType;

    protected string $extension;

    protected string $diskName;

    protected array $allowedMimeTypes;

    protected MediaFormat $mediaFormat;

    public function setSubject(Model $model): self
    {
        $this->subject = $model;

        return $this;
    }

    public function setFile($file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @throws UnknownType
     * @throws MimeTypeNotAllowed
     * @throws MediaFormatDoesNotExist
     * @throws ConfigDoesNotExist
     * @throws DiskDoesNotExist
     * @throws MimeTypeNotDefined
     * @throws FileTooBig
     */
    public function toMediaCollection(string $collectionName = 'default', string $diskName = ''): Media
    {
        if (is_null(config('media'))) {
            throw ConfigDoesNotExist::create();
        }

        $this->extension = $this->getExtension();

        $this->mimeType = $this->getMimeType();

        if (!$this->isValidatedMimeType($collectionName)) {
            throw MimeTypeNotAllowed::create($this->extension, $this->allowedMimeTypes);
        }

        if (!$this->isValidatedFileSize($collectionName)) {
            throw FileTooBig::create($this->fileSize);
        }

        if (!$this->isValidatedDisk($diskName, $collectionName)) {
            throw DiskDoesNotExist::create($this->diskName);
        }

        $media = Media::create([
            'model_id' => $this->subject->id,
            'model_type' => get_class($this->subject),
            'collection_name' => $collectionName,
            'file_name' => $this->fileName = uniqid(),
            'mime_type' => $this->mimeType,
            'size' => $this->fileSize,
            'disk' => $this->diskName,
        ]);

        $this->attachMedia($media, $collectionName);

        return $media;
    }

    protected function getExtension()
    {
        if (is_string($this->file))
            return pathinfo($this->file, PATHINFO_EXTENSION);

        if ($this->file instanceof UploadedFile)
            return $this->file->getClientOriginalExtension();

        throw UnknownType::create();
    }

    protected function getMimeType()
    {
        foreach (config('media.valid_media_mimetype') as $type) {
            if (strpos($type, $this->extension) !== false) {
                return $type;
            }
        }

        throw MimeTypeNotDefined::create($this->extension);
    }

    protected function isValidatedMimeType(string $collectionName = 'default'): bool
    {
        $this->setMediaFormat();

        $this->setAllowedMimeType($collectionName);

        return in_array($this->mimeType, $this->allowedMimeTypes);
    }

    protected function setAllowedMimeType(string $collectionName = 'default')
    {
        if ($collection = $this->getMediaCollection($collectionName)) {
            $this->allowedMimeTypes = Arr::flatten($collection->acceptsMimeTypes);

            if (empty($this->allowedMimeTypes)) {
                $this->allowedMimeTypes = array_filter(config('media.valid_media_mimetype'),
                    fn($item) => strpos($item, class_basename($this->mediaFormat)) !== false
                );
            }
        }
    }

    protected function setMediaFormat()
    {
        $type = explode('/', $this->mimeType)[0];
        $class = 'k90mirzaei\\Media\\Support\\Formats\\' . ucwords($type);

        if (!class_exists($class)) {
            throw MediaFormatDoesNotExist::create($type);
        }

        $this->mediaFormat = new $class;
    }

    protected function isValidatedFileSize(string $collectionName = 'default'): bool
    {
        $maxFileSize = (int)config('media.max_file_size');

        if (is_string($this->file)) {
            $header = get_headers($this->file, 1);
            $this->fileSize = $header["Content-Length"] ?? 0;
        }

        if ($this->file instanceof UploadedFile) {
            $file = new File($this->file);
            $this->fileSize = $file->getSize();
        }

        if ($collection = $this->getMediaCollection($collectionName)) {
            $maxFileSize = $collection->maxFileSize > 0 ? $collection->maxFileSize : $maxFileSize;
        }

        return $this->fileSize <= $maxFileSize;
    }

    protected function isValidatedDisk(string $diskName, string $collectionName): bool
    {
        return $this->ensureDiskExists($this->diskName = $this->getDiskName($diskName, $collectionName));
    }

    protected function getDiskName(string $diskName, string $collectionName): string
    {
        if ($diskName !== '') {
            return $diskName;
        }

        if ($collection = $this->getMediaCollection($collectionName)) {
            $collectionDiskName = $collection->diskName;

            if ($collectionDiskName !== '') {
                return $collectionDiskName;
            }
        }

        return config('media.disk_name');
    }

    protected function ensureDiskExists(string $diskName): bool
    {
        return !is_null(config("filesystems.disks.$diskName"));
    }

    protected function getMediaCollection(string $collectionName): ?MediaCollection
    {
        $this->subject->registerMediaCollections();

        return collect($this->subject->mediaCollections)
            ->first(fn(MediaCollection $collection) => $collection->name === $collectionName);
    }

    protected function attachMedia(Media $media, string $collectionName = 'default')
    {
        $collection = $this->getMediaCollection($collectionName);

        $this->mediaFormat->upload($this->file, $media, $collection);

        if ($collection->collectionSizeLimit) {
            $collectionMedia = $this->subject->getMedia($media->collection_name);

            if ($collectionMedia->count() > $collection->collectionSizeLimit) {
                $this->subject->clearMediaCollectionExcept($media->collection_name,
                    $collectionMedia->reverse()->take($collection->collectionSizeLimit));
            }
        }
    }
}