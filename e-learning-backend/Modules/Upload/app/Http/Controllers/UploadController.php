<?php

namespace Modules\Upload\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Upload\Http\Requests\PresignedUploadRequest;
use Modules\Upload\Http\Requests\UploadDocumentRequest;
use Modules\Upload\Http\Requests\UploadImageRequest;
use Modules\Upload\Http\Requests\UploadVideoRequest;
use Modules\Upload\Resources\MediaFileResource;
use Modules\Upload\Services\UploadService;

class UploadController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UploadService $uploadService
    ) {}

    public function uploadVideo(UploadVideoRequest $request): JsonResponse
    {
        $mediaFile = $this->uploadService->uploadVideo(
            $request->file('file'),
            auth('admin')->id()
        );

        return $this->success(new MediaFileResource($mediaFile), 'Upload video thành công.', 201);
    }

    public function uploadDocument(UploadDocumentRequest $request): JsonResponse
    {
        $mediaFile = $this->uploadService->uploadDocument(
            $request->file('file'),
            auth('admin')->id()
        );

        return $this->success(new MediaFileResource($mediaFile), 'Upload tài liệu thành công.', 201);
    }

    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        $mediaFile = $this->uploadService->uploadImage(
            $request->file('file'),
            $request->input('folder', 'images'),
            auth('admin')->id()
        );

        return $this->success(new MediaFileResource($mediaFile), 'Upload hình ảnh thành công.', 201);
    }

    public function presigned(PresignedUploadRequest $request): JsonResponse
    {
        $videoMeta = [];
        if ($request->type === 'video') {
            $videoMeta = array_filter([
                'duration' => $request->duration,
                'width' => $request->width,
                'height' => $request->height,
            ]);
        }

        $result = $this->uploadService->generatePresigned(
            $request->type,
            $request->filename,
            $request->mime_type,
            $request->size,
            $videoMeta,
            auth('admin')->id()
        );

        return $this->success($result, 'Presigned URL đã được tạo.');
    }

    public function confirm(int $id): JsonResponse
    {
        try {
            $mediaFile = $this->uploadService->confirmById($id);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new MediaFileResource($mediaFile), 'Xác nhận upload thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $mediaFile = $this->uploadService->findOrFail($id);

        if ($mediaFile->reference_count > 0) {
            return $this->error(
                "Không thể xóa: file đang được dùng bởi {$mediaFile->reference_count} bài giảng.",
                422
            );
        }

        Log::info('Admin xóa media file', [
            'media_file_id' => $mediaFile->id,
            'original_name' => $mediaFile->original_name,
            'deleted_by' => auth('admin')->id(),
            'uploaded_by' => $mediaFile->uploaded_by,
        ]);

        $this->uploadService->delete($mediaFile);

        return $this->success(null, 'Đã xóa file thành công.');
    }

    public function stream(int $id, Request $request)
    {
        // <video src="..."> cannot send Authorization headers — fallback to ?token= query param.
        $this->authorizeStreamRequest($request);

        $mediaFile = $this->uploadService->findOrFail($id);

        if ($mediaFile->disk !== 'local' && $mediaFile->disk !== 'public') {
            $presignedUrl = Storage::disk($mediaFile->disk)
                ->temporaryUrl($mediaFile->path, now()->addMinutes(60));

            return redirect()->away($presignedUrl);
        }

        $disk = Storage::disk($mediaFile->disk);
        $fullPath = $disk->path($mediaFile->path);

        if (! file_exists($fullPath)) {
            abort(404, 'File gốc không tồn tại trên hệ thống.');
        }

        $mimeType = $mediaFile->mime_type ?? 'video/mp4';
        $fileSize = filesize($fullPath);
        $lastModified = filemtime($fullPath);
        $etag = md5($mediaFile->id.'-'.$lastModified.'-'.$fileSize);

        if (
            $request->header('If-None-Match') === '"'.$etag.'"' ||
            $request->header('If-Modified-Since') === gmdate('D, d M Y H:i:s', $lastModified).' GMT'
        ) {
            return response('', 304, [
                'ETag' => '"'.$etag.'"',
                'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified).' GMT',
            ]);
        }

        $rangeHeader = $request->header('Range');
        if ($rangeHeader && preg_match('/bytes=(\d*)-(\d*)/i', $rangeHeader, $matches)) {
            $start = $matches[1] !== '' ? (int) $matches[1] : 0;
            $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

            $end = min($end, $fileSize - 1);
            $start = max(0, $start);

            if ($start > $end) {
                return response('', 416, ['Content-Range' => 'bytes */'.$fileSize]);
            }

            $length = $end - $start + 1;

            $stream = fopen($fullPath, 'rb');
            fseek($stream, $start);

            return response()->stream(
                function () use ($stream, $length) {
                    $remaining = $length;
                    $chunkSize = 1024 * 256;
                    while (! feof($stream) && $remaining > 0) {
                        $read = min($chunkSize, $remaining);
                        echo fread($stream, $read);
                        $remaining -= $read;
                        flush();
                    }
                    fclose($stream);
                },
                206,
                [
                    'Content-Type' => $mimeType,
                    'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
                    'Content-Length' => $length,
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'public, max-age=3600',
                    'ETag' => '"'.$etag.'"',
                    'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified).' GMT',
                ]
            );
        }

        $stream = fopen($fullPath, 'rb');

        return response()->stream(
            function () use ($stream) {
                $chunkSize = 1024 * 256;
                while (! feof($stream)) {
                    echo fread($stream, $chunkSize);
                    flush();
                }
                fclose($stream);
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
                'ETag' => '"'.$etag.'"',
                'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified).' GMT',
            ]
        );
    }

    private function authorizeStreamRequest(Request $request): void
    {
        if (Auth::guard('admin')->check() || Auth::guard('api')->check()) {
            return;
        }

        $rawToken = $request->query('token');
        if (! $rawToken) {
            abort(401, 'Unauthenticated.');
        }

        $accessToken = PersonalAccessToken::findToken($rawToken);
        if (! $accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            abort(401, 'Token không hợp lệ hoặc đã hết hạn.');
        }
    }
}
