<?php

namespace Modules\Upload\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Upload\Models\MediaFile;

class HlsService
{
    public function transcode(MediaFile $media): void
    {
        if ($media->disk !== 'local' && $media->disk !== 'public') {
            throw new \RuntimeException('HLS transcoding only supported for local disk.');
        }

        $inputPath = Storage::disk($media->disk)->path($media->path);

        if (! file_exists($inputPath)) {
            throw new \RuntimeException("Source file not found: {$inputPath}");
        }

        $outputDir = storage_path("app/public/hls/{$media->id}");
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // 16-byte AES-128 key
        $key = random_bytes(16);
        $keyHex = bin2hex($key);

        // Temporary files ffmpeg needs
        $keyFile = "{$outputDir}/enc.key";
        $keyInfoFile = "{$outputDir}/keyinfo.txt";

        file_put_contents($keyFile, $key);

        $keyUrl = url("api/v1/media/{$media->id}/hls-key");
        file_put_contents($keyInfoFile, "{$keyUrl}\n{$keyFile}");

        $playlistPath = "{$outputDir}/playlist.m3u8";
        $segmentPattern = "{$outputDir}/segment_%03d.ts";

        $cmd = sprintf(
            'ffmpeg -y -i %s -c:v copy -c:a copy -hls_time 10 -hls_key_info_file %s -hls_playlist_type vod -hls_segment_filename %s %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($keyInfoFile),
            escapeshellarg($segmentPattern),
            escapeshellarg($playlistPath)
        );

        Log::info('HLS transcode start', ['media_id' => $media->id, 'cmd' => $cmd]);

        exec($cmd, $output, $exitCode);

        // Clean up temp files — key is now embedded in segments, file no longer needed
        @unlink($keyFile);
        @unlink($keyInfoFile);

        if ($exitCode !== 0) {
            $detail = implode("\n", array_slice($output, -10));
            throw new \RuntimeException("FFmpeg failed (exit {$exitCode}): {$detail}");
        }

        $media->update([
            'hls_path' => "hls/{$media->id}/playlist.m3u8",
            'hls_key' => $keyHex,
            'hls_status' => 'ready',
        ]);

        Log::info('HLS transcode done', ['media_id' => $media->id]);
    }
}
