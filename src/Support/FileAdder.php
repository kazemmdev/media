<?php

namespace k90mirzaei\Media\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use k90mirzaei\Media\Exception\DiskDoesNotExist;
use k90mirzaei\Media\Exception\FileTooBig;
use k90mirzaei\Media\Exception\MediaCannotBeFetched;
use k90mirzaei\Media\Exception\MimeTypeNotAllowed;
use k90mirzaei\Media\Exception\UnknownType;
use k90mirzaei\Media\Model\Media;
use k90mirzaei\Media\Model\MediaCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileAdder
{
    protected $file;

    protected $rawFile;

    protected $fileSize;

    protected $storage;

    protected $imageManager;

    protected $allowedMimeTypes;

    protected ?Model $subject;

    protected string $mimeType = '';

    protected string $fileName = '';

    protected string $pathToFile = '';

    protected string $fileExtension = '';

    public function setSubject(Model $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function setFile($file): self
    {
        $this->rawFile = $file;
        $this->fileName = uniqid();
        $this->imageManager = new ImageManager();

        if (is_string($file)) {
            $this->fileExtension = $this->getExtensionFromURL();
            $this->mimeType = 'image/' . $this->fileExtension;

            return $this;
        }

        if ($file instanceof UploadedFile) {
            $this->fileExtension = $file->getClientOriginalExtension();
            $this->mimeType = 'image/' . $this->fileExtension;

            return $this;
        }

        throw UnknownType::create();
    }

    /**
     * @throws MediaCannotBeFetched
     * @throws FileTooBig
     * @throws MimeTypeNotAllowed
     * @throws DiskDoesNotExist
     */
    public function toMediaCollection(string $collectionName = 'default', string $diskName = ''): Media
    {
        $diskName = $this->determineDiskName($diskName, $collectionName);

        if (!$this->ensureDiskExists($diskName)) {
            throw DiskDoesNotExist::create($diskName);
        }

        $this->storage = Storage::disk($diskName);

        if (!$this->subject->exists) {
            throw MediaCannotBeFetched::create();
        }

        if (!$this->isValidSize($collectionName)) {
            throw FileTooBig::create($this->pathToFile, $this->storage->size($this->pathToFile));
        }

        if (!$this->isValidMimetype($collectionName)) {
            throw MimeTypeNotAllowed::create($this->file, $this->allowedMimeTypes);
        }

        $media = Media::create([
            'model_id' => $this->subject->id,
            'model_type' => get_class($this->subject),
            'file_name' =>  $this->fileName,
            'mime_type' =>   $this->mimeType,
            'collection_name' => $collectionName,
            'disk' => $diskName,
            'file' => $this->getTemporaryFile($collectionName),
        ]);

        $this->attachMedia($media);

        return $media;
    }


    protected function isValidSize(string $collectionName = 'default'): bool
    {
        if (is_string($this->rawFile)) {
            $header = get_headers($this->rawFile, 1);
            $size = $header["Content-Length"] ?? 0;

            return $size <= config('media.max_file_size');
        }

        if ($this->rawFile instanceof UploadedFile) {
            $validation = Validator::make(
                ['file' => new File($this->rawFile)],
                ['file' => 'max:' . ((int)config('media.max_file_size') / 1024)]
            );

            return !$validation->fails();
        }

        return true;
    }

    protected function isValidMimetype(string $collectionName = 'default'): bool
    {
        if ($collection = $this->getMediaCollection($collectionName)) {
            $this->allowedMimeTypes = Arr::flatten($collection->acceptsMimeTypes);

            if (empty($this->allowedMimeTypes)) {
                $this->allowedMimeTypes = config('media.valid_media_mimetype');
            }
        }

        if (is_string($this->rawFile)) {
            $header = get_headers($this->rawFile, 1);
            $type = $header["Content-Type"];

            return in_array(strtolower($type), config('media.valid_media_mimetype'));
        }

        if ($this->rawFile instanceof UploadedFile) {
            $validation = Validator::make(
                ['file' => new File($this->rawFile)],
                ['file' => 'mimetypes:' . implode(',', $this->allowedMimeTypes)]
            );
            return !$validation->fails();
        }

        return true;
    }

    protected function getTemporaryFile(string $collectionName = 'default')
    {
        $maxWidth = (int)config('media.max_file_width');

        if ($collection = $this->getMediaCollection($collectionName)) {
            $maxWidth = $collection->maxWidth !== 0 ? $collection->maxWidth : $maxWidth;
        }

        $file = $this->imageManager->make($this->rawFile)->encode($this->fileExtension);

        if ($collection->fitResize != 0) {
            if ($file->width() > $collection->fitResize) {
                $newfile = $this->imageManager->make($this->rawFile)
                    ->fit($collection->fitResize, null, function ($c) {
                        $c->upsize();
                    });

            } else $newfile = $this->imageManager->make($this->rawFile)
                ->fit($file->width(), null, function ($c) {
                    $c->upsize();
                });

            $file->destroy();

            return $newfile->encode($this->fileExtension);
        }


        if ($file->width() > $maxWidth) {
            $file->destroy();

            return $this->imageManager->make($this->rawFile)->resize($maxWidth, null, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            })->encode($this->fileExtension);
        }

        return $file;
    }

    protected function attachMedia(Media $media)
    {
        $this->pathToFile = "$media->id/{$this->getFileNameToStore()}";

        $this->uploadToStorage($media, $this->pathToFile, $this->file);

        $this->setUploadSizeFile($media, $this->pathToFile, $this->file);

        if ($collection = $this->getMediaCollection($media->collection_name)) {
            if ($collection->generateResponsiveImages) {
                $this->uploadResponsivesImage($media);
            }

            if ($collection->collectionSizeLimit) {
                $collectionMedia = $this->subject->getMedia($media->collection_name);

                if ($collectionMedia->count() > $collection->collectionSizeLimit) {
                    $this->subject->clearMediaCollectionExcept($media->collection_name, $collectionMedia->reverse()->take($collection->collectionSizeLimit));
                }
            }
        }
    }

    protected function setUploadSizeFile(Media $media, string $path, $file)
    {
        $this->fileSize = Storage::disk($media->disk)->size($path);

        $media->update(['size' => $this->fileSize]);
    }

    protected function uploadToStorage(Media $media, string $path, $file)
    {
        if (strtolower($this->fileExtension) == 'gif') {
            if ($this->file->filesize() > config('media.maxـgif_size')) {
                $this->file->destroy();
                throw new \Exception('حجم قابل قبول برای تصاویر gif حداکثر ' . MyFile::getHumanReadableSize(config('media.maxـgif_size')) . ' می‌باشد.');
            }

            $imagick = new \Imagick();
            $imagick->readImage($this->rawFile);


            // Save gif animation
            Storage::disk($media->disk)->makeDirectory($media->id);
            file_put_contents(public_path('storage/') . "um/$this->pathToFile", $imagick->getImagesBlob());

            $imagick->destroy();

            return true;
        }

        Storage::disk($media->disk)->put($path, $file->__toString());
    }

    protected function uploadResponsivesImage(Media $media)
    {
        $targetPaths = collect();

        if ($this->fileExtension != 'gif') {
            $widths = $this->calculateWidthsResponsives($this->fileSize, $this->file->width(), $this->file->height());

            foreach ($widths as $width) {
                $path = "/$media->id/$width/{$this->getFileNameToStore()}";

                $file = $this->imageManager->make($this->file)->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode($this->fileExtension);

                $this->uploadToStorage($media, $path, $file);

                $targetPaths->push($path);
            }
        }

        // save tiny placeholder
        $tiny = $this->imageManager->make($this->file)->resize(20, null, function ($constraint) {
            $constraint->aspectRatio();
        })->blur(1)->encode($this->fileExtension);

        $path = "/$media->id/tiny/{$this->getFileNameToStore()}";
        $this->uploadToStorage($media, $path, $tiny);

        $targetPaths->push($path);
        $media->update(['responsive_images' => $targetPaths]);

        $tiny->destroy();
    }

    protected function calculateWidthsResponsives(int $fileSize, int $width, int $height): Collection
    {
        $targetWidths = collect();

        $ratio = $height / $width;
        $area = $height * $width;

        $predictedFileSize = $fileSize;
        $pixelPrice = $predictedFileSize / $area;

        while (true) {
            $predictedFileSize *= 0.8;

            $newWidth = (int)floor(sqrt(($predictedFileSize / $pixelPrice) / $ratio));

            if ($this->finishedCalculating($predictedFileSize, $newWidth) || count($targetWidths) > 5) {
                return $targetWidths;
            }

            if ($newWidth <= 1200)
                $targetWidths->push($newWidth);
        }
    }

    protected function finishedCalculating(int $predictedFileSize, int $newWidth): bool
    {
        if ($newWidth < 20) {
            return true;
        }

        if ($predictedFileSize < (1024 * 50)) {
            return true;
        }

        return false;
    }

    protected function determineDiskName(string $diskName, string $collectionName): string
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

    protected function getExtensionFromURL(): string
    {
        return pathinfo($this->rawFile, PATHINFO_EXTENSION);
    }

    protected function getFileNameToStore(): string
    {
        return $this->fileName . '.' . $this->fileExtension;
    }
}