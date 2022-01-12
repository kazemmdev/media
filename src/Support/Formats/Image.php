<?php

namespace k90mirzaei\Media\Support\Formats;

use Illuminate\Support\Collection;
use Intervention\Image\ImageManager;
use k90mirzaei\Media\Facade\WidthsGenerator;
use k90mirzaei\Media\Model\Media;
use k90mirzaei\Media\Model\MediaCollection;

class Image extends MediaFormat
{
    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager();
    }

    public function upload($file, Media $media, MediaCollection $mediaCollection)
    {
        $this->media = $media;

        $this->setTemporaryFile($file);

        // proccess to optimize image
        if ($mediaCollection->maxWidth !== 0 || (int)config('media.max_file_width') !== 0) {
            $this->processToOptimizeImage($mediaCollection);
        }

        $this->putFileIntoStorage($this->media->getPath(), $this->file);

        if ($mediaCollection->generateResponsiveImages) {
            $this->media->update(['responsive_images' => $this->uploadResponsiveImage()]);
        }
    }

    protected function setTemporaryFile($file)
    {
        $this->file = $this->imageManager->make($file)->encode($this->media->getExtension());
    }

    protected function uploadResponsiveImage(): Collection
    {
        $targetPaths = collect();

        $calculated_widths = WidthsGenerator::make($this->file, $this->media);

        foreach ($calculated_widths as $width) {
            $path = "/{$this->media->id}/{$width}/{$this->media->getFileName()}";

            $file = $this->imageManager->make($this->file)
                ->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                })
                ->encode($this->media->getExtension());

            $this->putFileIntoStorage($path, $file);

            $targetPaths->push($path);

        }

        // save tiny placeholder
        $tiny = $this->imageManager->make($this->file)
            ->resize(20, null, function ($constraint) {
                $constraint->aspectRatio();
            })
            ->blur(1)->encode($this->media->getExtension());

        $path = "/{$this->media->id}/tiny/{$this->media->getFileName()}";

        $this->putFileIntoStorage($path, $tiny);

        $targetPaths->push($path);

        $tiny->destroy();

        return $targetPaths;
    }

    protected function processToOptimizeImage(MediaCollection $mediaCollection)
    {
        $maxWidth = $mediaCollection->maxWidth || (int)config('media.max_image_width');

        if ($this->file->width() > $maxWidth) {
            $this->file = $this->imageManager->make($this->file)
                ->resize($maxWidth, null, function ($image) {
                    $image->aspectRatio();
                    $image->upsize();
                })->encode($this->media->getExtension());

            $this->media->update(['size' => $this->file->filesize()]);
        }
    }
}