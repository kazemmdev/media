<?php

namespace k90mirzaei\Media\Console;

use Illuminate\Console\Command;
use k90mirzaei\Media\Model\Media;

class CleanCommand extends Command
{
    protected $signature = "media:clean";

    protected $description = "Clean all media file from storage without related model";

    public function handle()
    {
        Media::all()->each(function ($media) {
            if (is_null($media->model)) {
                $media->delete();
            }
        });

        $this->info('all media file from storage without related model is deleted');
    }
}