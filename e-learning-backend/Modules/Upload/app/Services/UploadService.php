<?php

namespace Modules\Upload\Services;

use Exception;
use getID3;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Upload\Models\MediaFile;

class UploadService
{
    public function findOrFail(int $id): MediaFile
    {
        return MediaFile::findOrFail($id);
    }

    public function uploadVideo(UploadedFile $file, ?int $uploadedBy = null): MediaFile
    {
        $metadata = $this->extractVideoMetadata($file);
        $fileData = $this->storeFile($file, 'videos');

        return MediaFile::create(array_merge([
            'disk' => $fileData['disk'],
            'type' => 'video',
            'original_name' => $file->getClientOriginalName(),
            'path' => $fileData['path'],
            'url' => $fileData['url'],
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'ready',
            'uploaded_by' => $uploadedBy,
        ], $metadata));
    }

    public function uploadDocument(UploadedFile $file, ?int $uploadedBy = null): MediaFile
    {
        $fileData = $this->storeFile($file, 'documents');

        return MediaFile::create([
            'disk' => $fileData['disk'],
            'type' => 'document',
            'original_name' => $file->getClientOriginalName(),
            'path' => $fileData['path'],
            'url' => $fileData['url'],
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'ready',
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function uploadImage(UploadedFile $file, string $folder = 'images', ?int $uploadedBy = null): MediaFile
    {
        $fileData = $this->storeFile($file, $folder);

        return MediaFile::create([
            'disk' => $fileData['disk'],
            'type' => 'image',
            'original_name' => $file->getClientOriginalName(),
            'path' => $fileData['path'],
            'url' => $fileData['url'],
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'ready',
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function generatePresigned(string $type, string $filename, string $mimeType, int $size, array $videoMeta = [], ?int $uploadedBy = null): array
    {
        $disk = 's3';
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $folder = match ($type) {
            'video' => 'videos',
            'document' => 'documents',
            'image' => 'images',
            default => 'misc',
        };
        $path = $folder.'/'.Str::uuid().'.'.$extension;

        $presignedUrl = Storage::disk($disk)->temporaryUploadUrl($path, now()->addMinutes(15));
        $url = Storage::disk($disk)->url($path);

        $mediaFile = MediaFile::create(array_merge([
            'disk' => $disk,
            'type' => $type,
            'original_name' => $filename,
            'path' => $path,
            'url' => $url,
            'mime_type' => $mimeType,
            'size' => $size,
            'status' => 'pending',
            'uploaded_by' => $uploadedBy,
        ], $videoMeta));

        return [
            'presigned_url' => $presignedUrl,
            'media_file_id' => $mediaFile->id,
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ];
    }

    public function confirmById(int $id): MediaFile
    {
        return DB::transaction(function () use ($id) {
            $mediaFile = MediaFile::where('id', $id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();

            return $this->confirmUpload($mediaFile);
        });
    }

    public function confirmUpload(MediaFile $mediaFile): MediaFile
    {
        $disk = Storage::disk($mediaFile->disk);

        if (! $disk->exists($mediaFile->path)) {
            throw new Exception('File chưa tồn tại trên storage. Upload lại hoặc thử lại sau.');
        }

        $updateData = ['status' => 'ready'];
        try {
            $actualMime = $disk->mimeType($mediaFile->path);
            if ($actualMime) {
                $updateData['mime_type'] = $actualMime;
            }
        } catch (Exception $e) {
            Log::warning('Không thể verify MIME type cho media_file #'.$mediaFile->id, [
                'error' => $e->getMessage(),
            ]);
        }

        $mediaFile->update($updateData);

        return $mediaFile;
    }

    public function delete(MediaFile $mediaFile): void
    {
        Storage::disk($mediaFile->disk)->delete($mediaFile->path);
        $mediaFile->delete();
    }

    private function extractVideoMetadata(UploadedFile $file): array
    {
        try {
            $getID3 = new getID3;
            $info = $getID3->analyze($file->getRealPath());

            return [
                'duration' => (int) round($info['playtime_seconds'] ?? 0),
                'width' => $info['video']['resolution_x'] ?? null,
                'height' => $info['video']['resolution_y'] ?? null,
                'bitrate' => (int) ($info['bitrate'] ?? 0),
                'codec' => $info['video']['codec'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('getID3 failed: '.$e->getMessage(), [
                'file' => $file->getClientOriginalName(),
            ]);

            return [];
        }
    }

    private function storeFile(UploadedFile $file, string $folder): array
    {
        $diskName = $this->diskName();
        $fileName = Str::uuid().'.'.($file->guessExtension() ?? $file->getClientOriginalExtension());
        $path = $file->storeAs($folder, $fileName, $diskName);

        return [
            'disk' => $diskName,
            'path' => $path,
            'url' => Storage::disk($diskName)->url($path),
        ];
    }

    private function diskName(): string
    {
        return config('filesystems.default');
    }
}
