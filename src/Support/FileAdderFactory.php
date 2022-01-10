<?php

namespace k90mirzaei\Media\Support;

use Illuminate\Database\Eloquent\Model;

class FileAdderFactory
{
    public static function create(Model $subject, $file): FileAdder
    {
        $fileAdder = app(FileAdder::class);

        return $fileAdder->setSubject($subject)->setFile($file);
    }
}