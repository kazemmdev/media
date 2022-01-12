<?php

namespace k90mirzaei\Media\Support\Formats;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Intervention\Image\ImageManager;
use k90mirzaei\Media\Model\Media;
use k90mirzaei\Media\Model\MediaCollection;

class Gif extends MediaFormat
{
    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager();
    }

    /**
     * @throws \ImagickException
     */
    public function upload($file, Media $media, MediaCollection $mediaCollection)
    {
        $this->media = $media;

        $this->setTemporaryFile($file);

        // proccess to optimize image
        if ($mediaCollection->maxWidth !== 0 || (int)config('media.max_gif_width') !== 0) {
            $this->processToOptimizeImage($mediaCollection);
        }

        $this->putFileIntoStorage($this->media->getPath(), $file);

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

        // save tiny placeholder
        $tiny = $this->imageManager->make($this->file)
            ->resize(20, null, fn($constraint) => $constraint->aspectRatio())
            ->blur(1)->encode($this->media->getExtension());

        $path = "/{$this->media->id}/tiny/{$this->media->getFileName()}";

        $this->putFileIntoStorage($path, $tiny);

        $targetPaths->push($path);

        $tiny->destroy();

        return $targetPaths;
    }

    /**
     * @throws \ImagickException
     */
    protected function processToOptimizeImage(MediaCollection $mediaCollection)
    {
        $maxWidth = $mediaCollection->maxWidth || (int)config('media.max_gif_width');

        if ($this->file->width() > $maxWidth) {

            $imagick = new Imagick();
            $imagick->readImage($this->rawFile);

            // Save gif animation
            Storage::disk($this->media->disk)->makeDirectory($this->media->id);
            file_put_contents(public_path('storage/') . "um/{$this->media->getPath()}", $imagick->getImagesBlob());

            $imagick->destroy();
        }
    }
}