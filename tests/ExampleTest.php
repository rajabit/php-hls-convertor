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
            "tmp/BbKCGYV3c6btsFhRnySBUHIDOjQEsPo1nUu0ADyw.mp4",
            array([
                "title" => "Maryam churche Spanish nar.mp4",
                "language" => "en",
                "default" => false,
                "file" => "tmp/56G7smlnsTfwUbuR0QuegwlWxwOL4RNgC7TLkwYK.mp4"
            ]),
            array(
                "audio_quality" => "90",
                "audio_type" => "aac",
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
