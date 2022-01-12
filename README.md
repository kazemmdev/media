# Associate files with Eloquent models

[![Latest Version](https://img.shields.io/github/release/k90mirzaei/media.svg?style=flat-square)](https://github.com/k90mirzaei/media/releases)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/k90mirzaei/media/run-tests?label=tests)
[![Total Downloads](https://img.shields.io/packagist/dt/k90mirzaei/media.svg?style=flat-square)](https://packagist.org/packages/k90mirzaei/media)

This package can associate all sorts of files with Eloquent models. It provides a
simple API to work with.

Here are a few short examples of what you can do:

```php
$newsItem = News::find(1);
$newsItem->addMedia($pathToFile)->toMediaCollection('images');
```

It can handle your uploads directly:

```php
$newsItem->addMedia($request->file('image'))->toMediaCollection('images');
```

Want to store some large files on another filesystem? No problem:

```php
$newsItem->addMedia($smallFile)->toMediaCollection('downloads', 'local');
$newsItem->addMedia($bigFile)->toMediaCollection('downloads', 's3');
```

The storage of the files is handled by [Laravel's Filesystem](https://laravel.com/docs/filesystem),
so you can use any filesystem you like. Additionally the package can create image manipulations
on images, audios and videos.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Testing

You can run the tests with:

```bash
vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
