<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;

class ConverterController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'video' => [
                'required',
                'file'
            ],
            'audios' => [
                'nullable',
                'array'
            ],
            'audios.*.title' => [
                'required',
                'max:64'
            ],
            'audios.*.language' => [
                'required',
                'max:3'
            ],
            'qualities' => [
                'required',
                'array'
            ],
            'hls_time' => [
                'required',
                'numeric',
                'min:5',
                'max:30'
            ],
            'audio_type' => [
                'required',
                'in:aac'
            ],
            'audio_quality' => [
                'required',
                'numeric',
                'min:60',
                'max:120'
            ],
            'timeout' => [
                'required',
                'numeric',
                'min:1024',
                'max:102400'
            ],
            'threads' => [
                'required',
                'numeric',
                'min:1',
                'max:24'
            ]
        ], [
            'qualities.required' => 'Atleast select 1 export quality',
            'audios.*.title.required' => 'Title is required',
            'audios.*.file.required' => 'File is required',
            'audios.*.language.required' => 'Language is required',
            'audios.*.language.max' => 'The Language must not be greater than 3 characters.',
            'audios.*.language.title' => 'The Title must not be greater than 64 characters.',
        ]);

        if (Cache::store('redis')->get('status', 'inactive') == 'active') {
            return response()->json([
                'video' => 'converter currently in use'
            ], 422);
        }

        Cache::store('redis')->put('status', 'active', Carbon::now()->addHour());

        $file = new Filesystem;
        $file->cleanDirectory(storage_path('app/tmp'));

        $video = $request->file('video')->store('tmp');
        $audios = [];

        foreach ($request->input('audios') as $index => $audio) {
            $audios[] = [
                'title' => $audio['title'],
                'language' => $audio['language'],
                'default' => $audio['default'] ?? false,
                'file' => $request->file("audios_$index")->store('tmp')
            ];
        }

        

        return response()->json($video);
    }
}
