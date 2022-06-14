<?php

namespace App\Jobs;

use Illuminate\Filesystem\Filesystem;
use Monolog\Logger;
use Streaming\Format\X264;
use Streaming\Representation;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Cache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

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

            $audioCount = count($this->audios);
            if ($audioCount) {
                  $oldmask = umask(0);
                  mkdir("$export/audio", 0777);
                  umask($oldmask);
                  Cache::store('redis')->put("status-{$this->uniqueId}-percentage", 0);

                  foreach ($this->audios as $index => $audio) {
                        $oldmask = umask(0);
                        mkdir("$export/audio/{$audio['language']}", 0777);
                        umask($oldmask);
                        Cache::store('redis')->put("status-{$this->uniqueId}", "Converting Audio ({$audio['language']})");
                        $command = "'/usr/bin/ffmpeg' '-y' '-i' '" . storage_path("app/{$audio['file']}") . "' '-c:a' '{$this->config['audio_type']}' '-b:a' '{$this->config['audio_quality']}k' '-vn' '-hls_time' '{$this->config['hls_time']}' '-hls_allow_cache' '1' '-hls_list_size' '0' '-keyint_min' '25' '-g' '250' '-sc_threshold' '40' '-hls_segment_filename' '$export/audio/{$audio['language']}/main_%04d.ts' '-strict' '-2' '$export/audio/{$audio['language']}/main.m3u8'";
                        shell_exec($command);
                        Cache::store('redis')->put("status-{$this->uniqueId}-percentage", ((($index + 1) / $audioCount) * 100));
                  }

                  Cache::store('redis')->put("status-{$this->uniqueId}", "Merging audios");
                  $this->merge_audios($this->audios, "$export/main.m3u8");
            }

            Cache::store('redis')->put("status-{$this->uniqueId}", "Zipping");
            Cache::store('redis')->put("status-{$this->uniqueId}-percentage", "indeterminate");

            $rootPath = realpath($export);
            $zipFile = storage_path("app/export/{$this->uniqueId}.zip");

            $zip = new ZipArchive();
            $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($files as $name => $file) {
                  if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($rootPath) + 1);
                        $zip->addFile($filePath, $relativePath);
                  }
            }

            $zip->close();

            Cache::store('redis')->put("status-{$this->uniqueId}", "Clearing temp");
            Cache::store('redis')->put("status-{$this->uniqueId}-percentage", "indeterminate");

            $fs =  new Filesystem;
            $fs->deleteDirectory($export);

            Cache::store('redis')->put('status', null);
            Cache::store('redis')->put("status-{$this->uniqueId}", "success");
            Cache::store('redis')->put("status-{$this->uniqueId}-message", "Convert finished successfully.");
            Cache::store('redis')->put("status-{$this->uniqueId}-percentage", null);
            Cache::store('redis')->put("status-{$this->uniqueId}-download", url("storage/export/{$this->uniqueId}.zip"));
      }



      public function merge_audios(array $audios, string $filePath)
      {
            $file = file_get_contents($filePath);

            $search = "EXT-X-VERSION";
            $lines = explode("#", $file);
            foreach ($lines as $index => $line) {
                  if (strpos($line, $search) === 0) {
                        $index += 1;

                        array_splice($lines, $index, 0, '');
                        foreach ($audios as $audio) {
                              array_splice($lines, $index, 0, 'EXT-X-MEDIA:TYPE=AUDIO,GROUP-ID="aac",LANGUAGE="' . $audio['language'] . '",NAME="' . $audio['title'] . '",DEFAULT=YES,AUTOSELECT=NO,URI="audio/' . $audio['language'] . '/main.m3u8"');
                        }
                        array_splice($lines, $index, 0, '');

                        break;
                  }
            }

            foreach ($lines as $index => $line) {
                  if (strpos($line, 'RESOLUTION')) {
                        $lines[$index] = str_replace('RESOLUTION', 'AUDIO="aac",RESOLUTION', $line);
                  }
            }

            $exp = "";
            foreach ($lines as $line) {
                  if (strlen($line) > 5) {
                        $exp .= (PHP_EOL . "#$line");
                  }
            }

            file_put_contents($filePath, $exp);
      }

      public function failed(\Throwable $exception)
      {
            Cache::store('redis')->put('status', null);
            Cache::store('redis')->put("status-{$this->uniqueId}", "failed");
            Cache::store('redis')->put("status-{$this->uniqueId}-message", $exception->getMessage());
            Cache::store('redis')->put("status-{$this->uniqueId}-percentage", null);
      }
}
