# Test Module: Upload Media (Video / Tài liệu / Ảnh)

> **Route BE:** `/api/v1/admin/upload/*`, `GET /api/v1/media/{id}/stream`
> **Auth:** Admin (guard: admin) — trừ stream

---

## QLCV — Việc cần làm

### Backend
- [ ] `uploadVideo`: validate mime (mp4/webm/mov), giới hạn kích thước (cấu hình trong `php.ini` và `config/filesystems`)
- [ ] `uploadDocument`: validate mime (pdf/doc/docx/ppt/xlsx)
- [ ] `uploadImage`: validate mime (jpg/png/webp), resize thumbnail
- [ ] `stream`: hỗ trợ `Range` header để seek video không bị giật
- [ ] `stream`: auth — lesson chưa enroll thì từ chối (hiện đang public hoặc check trong controller)
- [ ] `destroy`: xóa file vật lý khỏi storage khi xóa MediaFile
- [ ] `presigned` / `confirm`: flow S3 (để sau nếu deploy production)
- [ ] Xử lý lỗi khi file quá lớn: trả về 413 thay vì crash

### Frontend
- [ ] Upload form trong `CourseFormPage.vue` (thumbnail khóa học)
- [ ] Upload form trong lesson editor (video + document)
- [ ] Hiện progress bar khi upload file lớn
- [ ] Preview file sau khi upload (ảnh preview, video player nhỏ)
- [ ] Xóa file đã upload (trước khi lưu lesson)
- [ ] Giới hạn kích thước hiện rõ trên UI ("Tối đa 500MB cho video")

---

## MODULE 1 — Upload Video

### Test 1.1: Upload video hợp lệ ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn file `.mp4` → Upload | Progress bar chạy → Toast "Upload thành công" |
| 2 | Network | `POST /api/v1/admin/upload/video` → 201 |
| 3 | Response | Có `id`, `url`, `duration`, `size` |
| 4 | Storage | File xuất hiện trong `storage/app/public/videos/` |

### Test 1.2: Upload file không phải video

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn file `.pdf` vào input video | Lỗi FE hoặc BE: "Chỉ chấp nhận file video (mp4, webm, mov)" |

### Test 1.3: Upload video quá lớn

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | File > giới hạn (ví dụ 500MB) | Lỗi: "File vượt quá kích thước cho phép" |
| 2 | Network | 413 Request Entity Too Large |

### Test 1.4: Huỷ upload giữa chừng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đang upload → Click "Huỷ" | Upload dừng lại, không tạo record |

---

## MODULE 2 — Upload Tài liệu (PDF/Doc)

### Test 2.1: Upload PDF hợp lệ ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn file `.pdf` → Upload | Toast "Upload thành công" |
| 2 | Network | `POST /api/v1/admin/upload/document` → 201 |
| 3 | Response | Có `id`, `url`, `size`, `mime_type` |

### Test 2.2: Upload file không phải tài liệu

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn file `.exe` | Lỗi: "Định dạng file không được hỗ trợ" |

### Test 2.3: Xem tài liệu sau upload

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Sau khi upload thành công | Link xem trước / tải xuống hiện ra |

---

## MODULE 3 — Upload Ảnh (Thumbnail / Avatar)

### Test 3.1: Upload ảnh thumbnail khóa học ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Trong form khóa học → chọn ảnh → Upload | Preview ảnh hiện ngay |
| 2 | Network | `POST /api/v1/admin/upload/image` → 201 |
| 3 | Sau khi lưu khóa học | Thumbnail hiện đúng trên admin list |

### Test 3.2: Upload ảnh không hợp lệ

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn file `.gif` (nếu không hỗ trợ) | Lỗi định dạng |
| 2 | Chọn file `.txt` | Lỗi: "Chỉ chấp nhận file ảnh" |

---

## MODULE 4 — Stream Video

### Test 4.1: Stream video bình thường

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Học viên đã enroll → vào learn page → chọn bài video | Video load và phát được |
| 2 | Network | `GET /api/v1/media/{id}/stream` với header `Range: bytes=0-` → 206 Partial Content |

### Test 4.2: Seek video (tua)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Kéo thanh tiến trình lên giữa | Video tiếp tục từ điểm mới, không bị load lại từ đầu |
| 2 | Network | Range request mới: `Range: bytes=XXXXX-` → 206 |

### Test 4.3: Bảo vệ nội dung

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chưa enroll → gọi thẳng URL stream | 403 hoặc 401 |
| 2 | Chưa login → gọi stream URL | Redirect login hoặc 401 |

---

## MODULE 5 — Xóa file

### Test 5.1: Xóa file media

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Trong lesson editor → xóa video đã upload | File bị xóa khỏi danh sách |
| 2 | Network | `DELETE /api/v1/admin/upload/{id}` → 200 |
| 3 | Storage | File vật lý bị xóa khỏi `storage/` |

### Test 5.2: Xóa file đang được dùng bởi lesson

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | File đang gắn với lesson đã publish → xóa | Cảnh báo "File đang được sử dụng" hoặc cho phép xóa tùy design |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Upload video hợp lệ | ⬜ | |
| 1.2 File không phải video | ⬜ | |
| 1.3 Video quá lớn | ⬜ | |
| 1.4 Huỷ upload | ⬜ | |
| 2.1 Upload PDF | ⬜ | |
| 2.2 File không hợp lệ | ⬜ | |
| 2.3 Xem tài liệu sau upload | ⬜ | |
| 3.1 Upload thumbnail | ⬜ | |
| 3.2 Ảnh không hợp lệ | ⬜ | |
| 4.1 Stream video | ⬜ | |
| 4.2 Seek video | ⬜ | |
| 4.3 Bảo vệ nội dung | ⬜ | |
| 5.1 Xóa file | ⬜ | |
| 5.2 Xóa file đang dùng | ⬜ | |
