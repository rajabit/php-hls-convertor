<?php

namespace Tests;

use App\Jobs\ConvertJob;
use Illuminate\Support\Facades\Queue;

class ExampleTest extends TestCase
{
    public function test_that_base_endpoint_returns_a_successful_response()
    {
        $queue = dispatch(new ConvertJob(
            "8e6b8681-6907-4ee1-8ec7-7f777f790662",
            "tmp/dCcg4KwPxyxefL8nvFkvD3UTTcCgfnjYgfkdIZrs.mp4",
            array([
                "title" => "Maryam churche Spanish nar.mp4",
                "language" => "en",
                "default" => false,
                "file" => "tmp/vQD4KGZ9IwVGNd4i8J5LKFKYVJYncoFTjMvj2fqt.mp4"
            ], [
                "title" => "Maryam churche Italy.mp4",
                "language" => "en",
                "default" => false,
                "file" => "tmp/EN7p8LRfU9aw3UkYQa8tfmRaPaJHUpzCqbFNI2eA.mp4"
            ], [
                "title" => "Maryam churche German.mp4",
                "language" => "en",
                "default" => false,
                "file" => "tmp/5qvmf61qG5cOyt4W9d97WRf9gPzubXvRQITcm2ei.mp4"
            ], [
                "title" => "Maryam churche English.mp4",
                "language" => "en",
                "default" => false,
                "file" => "tmp/uzOt1YCwIDDXlEwQAsv3XpEQTymHPxrDPsL5qY5e.mp4"
            ]),
            array(
                "audio_quality" => "90",
                "hls_time" => "10",
                "qualities" => [
                    ["bitrate" => "150", "width" => "426", "height" => "240"]
                ],
                "threads" => "12",
                "timeout" => "14400"
            )
        ))->onConnection('redis');

        $queues = Queue::size();

        echo "\n\nqueues $queues\n\n";

        $this->assertTrue("Its ok");
    }
}
