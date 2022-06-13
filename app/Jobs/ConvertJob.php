<?php

namespace App\Jobs;

use Monolog\Logger;
use Streaming\Format\X264;
use Streaming\Representation;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Cache;

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
            Cache::store('redis')->put("status-{$this->uniqueId}", "Converting Video");

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

            $export = storage_path("app/export/$this->uniqueId");

            $oldmask = umask(0);
            mkdir($export, 0777);
            umask($oldmask);

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

            if (count($this->audios)) {
                  $oldmask = umask(0);
                  mkdir("$export/audio", 0777);
                  umask($oldmask);
                  foreach ($this->audios as $audio) {
                        $oldmask = umask(0);
                        mkdir("$export/audio/{$audio['language']}", 0777);
                        umask($oldmask);
                        Cache::store('redis')->put("status-{$this->uniqueId}", "Converting Audio ({$audio['language']})");
                        $command = "'/usr/bin/ffmpeg' '-y' '-i' '" . storage_path("app/{$audio['file']}") . "' '-c:a' '{$this->config['audio_type']}' '-b:a' '{$this->config['audio_quality']}k' '-vn' '-hls_time' '{$this->config['hls_time']}' '-hls_allow_cache' '1' '-hls_list_size' '0' '-keyint_min' '25' '-g' '250' '-sc_threshold' '40' '-hls_segment_filename' '$export/audio/{$audio['language']}/main_%04d.ts' '-strict' '-2' '$export/audio/{$audio['language']}/main.m3u8'";
                        shell_exec($command);
                  }
            }


            Cache::store('redis')->put('status', null);
            Cache::store('redis')->put("status-{$this->uniqueId}", "success");
            Cache::store('redis')->put("status-{$this->uniqueId}-message", $export);
            Cache::store('redis')->put("status-{$this->uniqueId}-percentage", null);
      }

      public function failed(\Throwable $exception)
      {
            Cache::store('redis')->put('status', null);
            Cache::store('redis')->put("status-{$this->uniqueId}", "failed");
            Cache::store('redis')->put("status-{$this->uniqueId}-message", $exception->getMessage());
            Cache::store('redis')->put("status-{$this->uniqueId}-percentage", null);
      }
}
