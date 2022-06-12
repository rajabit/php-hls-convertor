<?php

namespace App\Jobs;

use Monolog\Logger;
use Streaming\Format\X264;
use Streaming\Representation;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ConvertJob extends Job
{
      public array $audios, $config;
      public string $video,  $uniqueId;
      public int $timeout = 144000;
      public $failOnTimeout = true;

      public function __construct(string $uniqueId, string $video, array $audios, array $config)
      {
            $this->video = $video;
            $this->audios = $audios;
            $this->config = $config;
            $this->uniqueId = $uniqueId;
            $this->timeout = $config['timeout'];
      }

      public function handle()
      {
            Cache::store('redis')->put('status', $this->uniqueId);
            Cache::store('redis')->put("status-{$this->uniqueId}", "started");

            $log = new Logger('FFmpeg_Streaming');
            $log->pushHandler(new StreamHandler(storage_path("logs/convert.log")));

            $ffmpeg = \Streaming\FFMpeg::create(array(
                  "ffmpeg.binaries" => env('ffmpeg_binaries'),
                  "ffprobe.binaries" => env('ffprobe_binaries'),
                  "timeout" => $this->config['timeout'],
                  "ffmpeg.threads" => $this->config['threads'],
            ), $log);

            $array = [];

            foreach ($this->config['qualities'] as $item) {
                  $array[] = (new Representation)
                        ->setKiloBitrate($item['bitrate'])
                        ->setResize($item['width'], $item['height']);
            }

            Storage::makeDirectory("export/$this->uniqueId");
            $export = storage_path("app/export/$this->uniqueId");

            $video = $ffmpeg->open(storage_path("app/$this->video"));

            $progress = new X264();
            $progress->on('progress', function ($video, $format, $percentage) {
                  Cache::store('redis')->put("status-{$this->uniqueId}-percentage", $percentage);
            });

            $video->hls()
                  ->x264()
                  ->setFormat($progress)
                  ->addRepresentations($array)
                  ->setHlsAllowCache(true)
                  ->setHlsTime($this->config['hls_time'])
                  ->setSegSubDirectory("videos")
                  ->save("$export/main.m3u8");

            foreach ($this->audios as $audio) {
                  $command = "'/usr/bin/ffmpeg' '-y' '-i' '{$audio['file']}' '-c:a' '{$this->config['audio_type']}' '-b:a' 
                  '{$this->config['audio_quality']}k' '-vn' '-hls_time' '{$this->config['hls_time']}' '-hls_allow_cache' '1' '-hls_list_size' '0' 
                   '-keyint_min' '25' '-g' '250' '-sc_threshold' '40' '-hls_segment_filename' '$export/audio/{$audio['language']}/main_%04d.ts' 
                    '-strict' '-2' '$export/audio/{$audio['language']}/main.m3u8'";
                  shell_exec($command);
                  echo $command . "\n";
            }

            Cache::store('redis')->put('status', 'inactive');
            Cache::store('redis')->put("status-{$this->uniqueId}", "success");
            Cache::store('redis')->put("status-{$this->uniqueId}-message", $export);
      }

      public function failed(\Throwable $exception)
      {
            Cache::store('redis')->put('status', 'inactive');
            Cache::store('redis')->put("status-{$this->uniqueId}", "failed");
            Cache::store('redis')->put("status-{$this->uniqueId}-message", $exception->getMessage());
      }
}
