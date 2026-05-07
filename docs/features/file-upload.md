# Upload file và Media

## 1. Tổng quan

Module `Upload` xử lý tất cả file media (video, document, image). Hỗ trợ hai luồng:

| Luồng | Phù hợp với | Giới hạn |
|-------|------------|---------|
| **Local upload** | File nhỏ (<500MB video, <20MB doc) | Lưu trên server |
| **S3 Presigned URL** | File lớn (video HD, tối đa 5GB) | Upload thẳng từ browser lên S3 |

Tất cả upload routes yêu cầu `auth:admin`.

---

## 2. Bảng `media_files`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `type` | enum | `video` / `document` / `image` |
| `storage` | varchar | `local` / `s3` |
| `original_name` | varchar | Tên file gốc |
| `filename` | varchar | Tên file đã lưu (unique) |
| `path` | varchar | Path tương đối trên storage |
| `url` | varchar | Full URL hoặc S3 key |
| `size` | bigint | Kích thước byte |
| `mime_type` | varchar | MIME type |
| `duration` | int nullable | Thời lượng video (giây) |
| `status` | tinyint | 0 = pending (S3 chưa confirm), 1 = ready |

---

## 3. API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/api/v1/upload/video` | Upload video (local) |
| POST | `/api/v1/upload/document` | Upload document (local) |
| POST | `/api/v1/upload/image` | Upload image (local) |
| POST | `/api/v1/upload/presigned` | Lấy S3 presigned URL để upload trực tiếp |
| POST | `/api/v1/upload/{id}/confirm` | Xác nhận đã upload lên S3 xong |
| DELETE | `/api/v1/upload/{id}` | Xóa media file |
| GET | `/api/v1/media/{id}/stream` | Stream video (public, auth qua token) |

---

## 4. Luồng Local Upload

```
Admin chọn file → form upload
  │
  │  POST /api/v1/upload/video
  │  Content-Type: multipart/form-data
  │  Body: { file: <video file> }
  ▼
UploadController::uploadVideo()
  │
  ├── Validate:
  │     MIME: video/mp4, video/webm, video/quicktime, video/x-matroska
  │     Max size: 500MB
  │
  ├── Generate unique filename: uuid + extension
  ├── Store vào storage/app/public/videos/
  ├── Dùng getid3 để đọc duration (giây)
  ├── Tạo MediaFile record: { type: 'video', storage: 'local', path, size, duration, status: 1 }
  │
  └── Return: { id, url, duration, size }
```

**Document upload:** tương tự, MIME: pdf/doc/docx/txt, max 20MB, không đọc duration.

---

## 5. Luồng S3 Presigned Upload

Dùng cho file lớn (video HD). Browser upload trực tiếp lên S3, không qua backend server:

```
[Bước 1] Admin yêu cầu presigned URL
  │
  │  POST /api/v1/upload/presigned
  │  Body: { filename: "lecture.mp4", type: "video", size: 2147483648 }
  ▼
UploadController::presigned()
  │
  ├── Validate type: video/document/image, size <= 5GB
  ├── Generate S3 key: videos/{uuid}/lecture.mp4
  ├── Tạo MediaFile record: { status: 0 (pending), storage: 's3' }
  ├── Tạo S3 presigned PUT URL (TTL: 60 phút)
  └── Return: { media_id, presigned_url, s3_key }

[Bước 2] Browser upload thẳng lên S3
  │
  │  PUT {presigned_url}
  │  Content-Type: video/mp4
  │  Body: <file binary>
  ▼
S3 lưu file

[Bước 3] Confirm với backend
  │
  │  POST /api/v1/upload/{media_id}/confirm
  ▼
UploadController::confirm()
  │
  ├── Kiểm tra file tồn tại trên S3
  ├── Cập nhật MediaFile: status = 1 (ready)
  └── Return: { id, url }
```

---

## 6. Video Streaming

Endpoint `/api/v1/media/{id}/stream` phục vụ video đến trình phát của học viên:

```
GET /api/v1/media/{id}/stream
  │
  ├── Xác thực: Bearer header HOẶC ?token= query param
  │     (cần query param vì <video src="..."> không gửi Authorization header)
  │
  ├── Tìm MediaFile, kiểm tra type = 'video'
  │
  ├── [storage = 'local']
  │     ├── Đọc file size, mime_type
  │     ├── [Có Range header] → 206 Partial Content
  │     │     Content-Range: bytes {start}-{end}/{total}
  │     │     Content-Length: {chunk_size}
  │     │     → Stream chunk từ {start} đến {end}
  │     │
  │     └── [Không có Range] → 200 OK, stream toàn bộ
  │
  └── [storage = 's3']
        ├── Tạo S3 presigned GET URL (TTL: 5 phút)
        └── Redirect 302 → presigned URL
              (S3 tự xử lý Range requests cho S3-hosted video)
```

**Headers quan trọng:**
```
Accept-Ranges: bytes
ETag: {md5_of_file}
Cache-Control: private, max-age=3600
```

---

## 7. Gắn media vào bài học

Sau khi upload, admin lưu `media_id` vào lesson:

```
PATCH /api/v1/admin/lessons/{id}
Body: {
  "video_id": 42,        // media_file.id của video
  "type": "video"
}
```

Validation: `video_id` phải `exists:media_files,id`.

Khi student học bài, backend trả `stream_url = /api/v1/media/{video_id}/stream?token=...`.

---

## 8. Dọn dẹp orphaned media

Scheduled task chạy hàng ngày lúc 03:00:

```bash
php artisan media:prune-orphans
```

Lệnh này xóa các `media_files` không được reference bởi lesson nào (status = 1 nhưng không có lesson.video_id nào trỏ đến). Ngăn disk bị đầy bởi file upload nhưng không dùng.

---

## 9. Giới hạn upload

| Loại | MIME types được chấp nhận | Kích thước tối đa |
|------|--------------------------|-------------------|
| Video (local) | mp4, webm, quicktime, mkv | 500 MB |
| Document | pdf, doc, docx, txt | 20 MB |
| Image | jpeg, png, gif, webp | 5 MB |
| Video (S3 presigned) | bất kỳ video/* | 5 GB |
