<?php

namespace App\Jobs;

class ExampleJob extends Job
{
      public $video, $audios, $config;

      public function __construct(string $video, array $audios, array $config)
      {
            $this->video = $video;
            $this->audios = $audios;
            $this->config = $config;
      }

      public function handle()
      {
            
      }
}
