<?php

namespace percept\Article\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \percept\Article\BaseServiceProvider::class,
        ];
    }
}