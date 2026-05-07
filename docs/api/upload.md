# API Reference — Upload & Media

Base URL: `http://localhost:8000/api/v1`

Tất cả upload routes yêu cầu `auth:admin`.

---

## Local Upload

### POST `/admin/upload/video`

Upload video trực tiếp lên server.

**Request:** `multipart/form-data`
```
file: <video file>
```

**Giới hạn:**
- MIME: `video/mp4`, `video/webm`, `video/quicktime`, `video/x-matroska`
- Max size: **500 MB**

**Response 201:**
```json
{
  "data": {
    "id": 10,
    "url": "http://localhost:8000/storage/videos/abc123.mp4",
    "duration": 720,
    "size": 52428800,
    "mime_type": "video/mp4"
  }
}
```

---

### POST `/admin/upload/document`

**Request:** `multipart/form-data`
```
file: <document file>
```

**Giới hạn:**
- MIME: `application/pdf`, `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, `text/plain`
- Max size: **20 MB**

**Response 201:** Tương tự video nhưng không có `duration`.

---

### POST `/admin/upload/image`

**Giới hạn:**
- MIME: `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- Max size: **5 MB**

---

### DELETE `/admin/upload/{id}`

Xóa media file và file vật lý trên storage/S3.

---

## S3 Presigned Upload (cho file lớn)

### POST `/admin/upload/presigned`

Tạo presigned URL để browser upload thẳng lên S3, không qua server.

**Request Body:**
```json
{
  "filename": "lecture-01.mp4",
  "type": "video",
  "size": 2147483648
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `type` | required, in:video,document,image |
| `size` | required, max: 5GB (5368709120) |

**Response 200:**
```json
{
  "data": {
    "media_id": 15,
    "presigned_url": "https://s3.amazonaws.com/bucket/videos/uuid/lecture-01.mp4?X-Amz-...",
    "s3_key": "videos/uuid/lecture-01.mp4",
    "expires_in": 3600
  }
}
```

**Sau đó browser PUT file lên `presigned_url` trực tiếp.**

---

### POST `/admin/upload/{id}/confirm`

Xác nhận đã upload lên S3 xong. Cập nhật `media_files.status = 1`.

**Response 200:**
```json
{
  "data": {
    "id": 15,
    "url": "https://bucket.s3.amazonaws.com/videos/uuid/lecture-01.mp4",
    "status": 1
  }
}
```

---

## Media Streaming (Public)

### GET `/media/{id}/stream`

Stream video đến trình phát. Xác thực qua Bearer header **hoặc** query param `?token=`.

```
Authorization: Bearer <token>
# hoặc
GET /media/10/stream?token=<studentToken>
```

> Query param `?token=` cần thiết vì thẻ `<video src="...">` không thể gửi Authorization header.

**Request headers (tùy chọn):**
```
Range: bytes=0-1048575
```

**Response 200** (không có Range):
```
Content-Type: video/mp4
Content-Length: 52428800
Accept-Ranges: bytes
ETag: "abc123"
Cache-Control: private, max-age=3600
```

**Response 206** (có Range header — partial content):
```
Content-Type: video/mp4
Content-Range: bytes 0-1048575/52428800
Content-Length: 1048576
```

**Response cho S3 video:** Redirect `302` đến S3 presigned GET URL (TTL: 5 phút).

**Response 401:** Token không hợp lệ.
**Response 403:** Không có quyền xem (chưa enroll).
**Response 404:** Media không tồn tại.
