<?php

return [

    /*
     * The disk on which to store added files and derived images by default. Choose
     * one or more of the disks you've configured in config/filesystems.php.
     */
    'disk_name' => env('MEDIA_DISK', 'public'),

    /*
     * The maximum file size of an item in bytes.
     * Adding a larger file will result in an exception.
     */
    'max_file_size' => 1024 * 1024 * 5, // 5 Mb

    /**
     *
     */
    'max_image_width' => 1920,

    /**
     * The whole acceptable formats in our package
     */
    'valid_media_mimetype' => [
        'image/gif',
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/tif',
        'image/webp',
        'audio/mp3',
        'audio/wav',
        'audio/aiff',
        'video/mp4',
        'video/mov',
        'video/webm',
    ]
];
