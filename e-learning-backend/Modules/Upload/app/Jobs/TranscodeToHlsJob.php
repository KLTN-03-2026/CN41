<?php

namespace Modules\Upload\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Modules\Upload\Models\MediaFile;
use Modules\Upload\Services\HlsService;
use Throwable;

class TranscodeToHlsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600; // 10 minutes — large videos need time

    public function __construct(public readonly int $mediaId) {}

    public function handle(HlsService $service): void
    {
        $media = MediaFile::findOrFail($this->mediaId);
        $media->update(['hls_status' => 'processing']);

        Broadcast::on("private-hls.{$this->mediaId}")
            ->as('HlsProgress')
            ->with(['status' => 'processing', 'percent' => 0])
            ->send();

        $service->transcode($media);

        $media->update(['hls_status' => 'ready']);

        Broadcast::on("private-hls.{$this->mediaId}")
            ->as('HlsProgress')
            ->with(['status' => 'ready', 'percent' => 100])
            ->send();
    }

    public function failed(Throwable $exception): void
    {
        MediaFile::where('id', $this->mediaId)->update(['hls_status' => 'failed']);

        Broadcast::on("private-hls.{$this->mediaId}")
            ->as('HlsProgress')
            ->with(['status' => 'failed', 'percent' => 0])
            ->send();

        Log::error('TranscodeToHlsJob failed', [
            'media_id' => $this->mediaId,
            'error' => $exception->getMessage(),
        ]);
    }
}
