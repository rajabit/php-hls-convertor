<?php

namespace Tests;

use App\Jobs\ConvertJob;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Queue;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ExampleTest extends TestCase
{
    public function test_that_base_endpoint_returns_a_successful_response()
    {
        $fs =  new Filesystem;
        $fs->deleteDirectory("/home/mahdi/apps/php-hls-convertor/storage/app/home");
    }
}
