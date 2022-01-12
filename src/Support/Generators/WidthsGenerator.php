<?php

namespace k90mirzaei\Media\Support\Generators;

use Illuminate\Support\Collection;
use k90mirzaei\Media\Model\Media;

class WidthsGenerator
{
    const MAX_WIDTH = 1200;

    const MAX_WIDTH_COUNT = 5;

    const REDUCTION_COEF = 0.8;

    const MIN_IMAGE_WIDTH = 20;

    const MAX_PREDICTED_SIZE = 50 * 1024;


    public function make($file, Media $media): Collection
    {
        $targetWidths = collect();

        $ratio = $file->height() / $file->width();
        $area = $file->height() * $file->width();

        $predictedFileSize = $media->size;
        $pixelPrice = $predictedFileSize / $area;

        while (true) {
            $predictedFileSize *= self::REDUCTION_COEF;

            $newWidth = (int)floor(sqrt($predictedFileSize / ($ratio * $pixelPrice)));

            if ($this->finishedCalculating($predictedFileSize, $newWidth) || count($targetWidths) > self::MAX_WIDTH_COUNT) {
                return $targetWidths;
            }

            if ($newWidth <= self::MAX_WIDTH)
                $targetWidths->push($newWidth);
        }
    }

    protected function finishedCalculating(int $predictedFileSize, int $newWidth): bool
    {
        if ($newWidth < self::MIN_IMAGE_WIDTH) {
            return true;
        }

        if ($predictedFileSize < self::MAX_PREDICTED_SIZE) {
            return true;
        }

        return false;
    }
}