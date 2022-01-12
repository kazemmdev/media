<?php

namespace k90mirzaei\Media\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use k90mirzaei\Media\Exception\MediaDimensionInfeasible;
use k90mirzaei\Media\Facade\UrlGenerator;

class Media extends Model
{
    protected $table = 'media';

    protected $guarded = [];

    const UPDATED_AT = null;

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function getExtension(): string
    {
        $array = explode('/', $this->mime_type);

        return end($array);
    }

    public function getFileName(): string
    {
        return "{$this->file_name}.{$this->getExtension()}";
    }

    public function getPath(): string
    {
        return "{$this->id}/{$this->getFileName()}";
    }

    public function getUrl(): string
    {
        return UrlGenerator::setMedia($this)->getUrl($this->getPath());
    }

    public function getSrcset(): string
    {
        $str = '';
        $responsive_images = json_decode($this->responsive_images, true);

        if (is_array($responsive_images))
            foreach ($responsive_images as $responsive) {
                $path_elements = explode('/', $responsive);

                if (!is_array($path_elements))
                    return $str;

                $str .= UrlGenerator::setMedia($this)->getUrl($responsive) . $path_elements[2] !== 'tiny' ?
                    " {$path_elements[2]}w, " : "";
            }

        return $str;
    }

    public function getTinyPath(): string
    {
        $responsive_images = json_decode($this->responsive_images, true);

        if (is_array($responsive_images)) {
            $tiny_path = $responsive_images[array_key_last($responsive_images)];
        }

        return $tiny_path ?? '';
    }

    public function getTinyUrl(): string
    {
        return ($path = $this->getTinyPath()) !== '' ? UrlGenerator::setMedia($this)->getUrl($path) : '';
    }

    public function getDimensions(): array
    {
        if (strpos($this->mime_type, 'image') === false) {
            throw MediaDimensionInfeasible::create();
        }

        $image = Image::make($this->getUrl());

        return [
            'height' => $image->height(),
            'width' => $image->width()
        ];
    }

    public function getAspect(): float
    {
        $dim = $this->getDimensions();

        return $dim ? 100 * $dim['height'] / $dim['width'] : 66.6;
    }

    public function delete(): bool
    {
        $id = $this->id;
        $disk = $this->disk;

        if (parent::delete()) {
            Storage::disk($disk)->deleteDirectory("{$id}");
        }

        return true;
    }
}