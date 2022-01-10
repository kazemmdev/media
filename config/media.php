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
    'max_file_size' => 1024 * 1024 * 5,
    'maxـgif_size' => 1024 * 1024 * 2,

    /*
     * The maximum file width of an item in pixel.
     * Adding a larger file will transform to smaller one.
     */
    'max_file_width' => 800,
    'max_gif_width' => 600,

    /**
     * Maximum size of each article images
     */
    'max_posts_media_size' => 10 * 1024 * 1024,


    /**
     * Maximum size of each ticket attachment images
     */
    'max_ticket_file_size' => 2 * 1024 * 1024,


    /**
     * Maximum size of user avatar
     */
    'max_avatar_size' => 2 * 1024 * 1024,

    /**
     * The whole acceptable formats in our package
     */
    'valid_media_mimetype' => [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/gif',
        'image/webp',
        'image/tif',
    ]
];
