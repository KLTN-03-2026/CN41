# Tài liệu dự án E-Learning Marketplace

Bộ tài liệu kỹ thuật đầy đủ cho hệ thống E-Learning Marketplace — đồ án tốt nghiệp (KLTN) 2026.

---

## Mục lục nhanh

| Bạn muốn... | Xem tại |
|-------------|---------|
| Hiểu tổng quan hệ thống | [architecture/overview.md](architecture/overview.md) |
| Cài đặt môi trường local | [setup/local-dev.md](setup/local-dev.md) |
| Xem tất cả biến môi trường | [setup/environment.md](setup/environment.md) |
| Deploy lên production | [setup/deployment.md](setup/deployment.md) |
| Tra cứu API endpoint | [api/](#api-reference) |
| Hiểu một tính năng cụ thể | [features/](#features) |
| Hiểu kiến trúc backend/frontend | [architecture/](#architecture) |
| Chạy tests | [testing/README.md](testing/README.md) |

---

## Architecture

Tài liệu mô tả thiết kế kỹ thuật của hệ thống.

| File | Mô tả |
|------|-------|
| [architecture/overview.md](architecture/overview.md) | Kiến trúc tổng thể, luồng request, dual guard auth, tính năng nổi bật |
| [architecture/backend.md](architecture/backend.md) | Modular Monolith, Repository pattern, ApiResponse trait, FormRequest, Exception handler |
| [architecture/frontend.md](architecture/frontend.md) | Vue 3 SPA, Composable pattern, Pinia stores, Service layer, Router guards |
| [architecture/database.md](architecture/database.md) | ERD 27 bảng, conventions, soft delete cascade, NestedSet, migration timeline |

---

## Features

Tài liệu mô tả chi tiết từng tính năng: flow, cấu trúc dữ liệu, business logic.

| File | Mô tả |
|------|-------|
| [features/authentication.md](features/authentication.md) | Dual guard (admin/student), đăng ký, đăng nhập, xác minh email, reset password |
| [features/rbac.md](features/rbac.md) | Phân quyền Spatie — 3 roles, 35 permissions, v-permission directive |
| [features/course-management.md](features/course-management.md) | Vòng đời khóa học, sections/lessons, soft delete cascade, enroll |
| [features/learning-flow.md](features/learning-flow.md) | LearnPage, video tracking, HTTP Range streaming, tiến độ học |
| [features/quiz-system.md](features/quiz-system.md) | Quiz trắc nghiệm, AI generation async (Gemini), luồng làm bài |
| [features/payment-vnpay.md](features/payment-vnpay.md) | Tích hợp VNPay, IPN webhook, coupon race condition protection |
| [features/file-upload.md](features/file-upload.md) | Local upload, S3 presigned URL, video streaming (206 Partial Content) |
| [features/categories.md](features/categories.md) | Danh mục cây NestedSet, move node, flat-tree dropdown |
| [features/posts.md](features/posts.md) | Blog, nested comments, tags, moderation |
| [features/coupons.md](features/coupons.md) | Mã giảm giá, tính discount, race condition với lockForUpdate |

---

## API Reference

Tài liệu endpoint đầy đủ: method, path, middleware, request body, validation rules, response mẫu.

| File | Các endpoint chính |
|------|-------------------|
| [api/auth.md](api/auth.md) | Login, register, verify email, forgot/reset password |
| [api/courses.md](api/courses.md) | Admin CRUD, bulk ops, public list/detail, student enroll |
| [api/lessons.md](api/lessons.md) | Sections/Lessons CRUD, reorder, progress tracking |
| [api/categories.md](api/categories.md) | Tree, flat-tree, move, ancestors/descendants |
| [api/quiz.md](api/quiz.md) | Quiz CRUD, AI generate async, poll job status, student submit |
| [api/payment.md](api/payment.md) | Tạo đơn, VNPay callbacks, my-orders, revenue stats |
| [api/upload.md](api/upload.md) | Local upload, S3 presigned, confirm, media stream |
| [api/posts.md](api/posts.md) | Posts CRUD, tags, comments, toggle publish |
| [api/coupons.md](api/coupons.md) | Admin CRUD, validate coupon, available list |

**Base URL:** `http://localhost:8000/api/v1`

**Response format chuẩn:**
```json
{ "success": true|false, "message": "...", "data": {...}|[...], "pagination": {...} }
```

---

## Setup

| File | Mô tả |
|------|-------|
| [setup/local-dev.md](setup/local-dev.md) | Cài đặt từ đầu, khởi động, reset DB, chạy tests |
| [setup/environment.md](setup/environment.md) | Toàn bộ biến `.env` backend + frontend, so sánh local vs production |
| [setup/deployment.md](setup/deployment.md) | Nginx, Supervisor, Cron, checklist go-live |

---

## Testing

| File | Mô tả |
|------|-------|
| [testing/README.md](testing/README.md) | Tổng quan test coverage, cách chạy |
| [testing/test-auth.md](testing/test-auth.md) | Test cases xác thực |
| [testing/test-courses-admin.md](testing/test-courses-admin.md) | Test cases quản lý khóa học |
| [testing/test-sections-lessons.md](testing/test-sections-lessons.md) | Test cases sections & lessons |
| [testing/test-categories.md](testing/test-categories.md) | Test cases danh mục |
| [testing/test-payment.md](testing/test-payment.md) | Test cases thanh toán |
| [testing/test-coupons.md](testing/test-coupons.md) | Test cases mã giảm giá (bao gồm concurrency) |
| [testing/test-posts.md](testing/test-posts.md) | Test cases bài viết |
| [testing/test-learn-page.md](testing/test-learn-page.md) | Test cases trang học |
| [testing/test-upload.md](testing/test-upload.md) | Test cases upload file |

**Chạy tests backend:**
```bash
cd e-learning-backend
php artisan test              # tất cả
php artisan test --parallel   # song song (nhanh hơn)
php artisan test --filter=CategoryTest
```

---

## Stack nhanh

```
Backend:   Laravel 12 (PHP 8.2) + Nwidart Modules + Sanctum + Spatie Permission
Frontend:  Vue 3 + TypeScript + Pinia + Tailwind CSS + Vite
Database:  MySQL 8 (27 tables)
Queue:     Laravel Queue (database driver) — dùng cho AI quiz generation
AI:        Google Gemini 2.0 Flash — sinh câu hỏi trắc nghiệm
Payment:   VNPay sandbox/production
```

---

## Ports local dev

| Service | URL |
|---------|-----|
| Backend API | http://localhost:8000 |
| Frontend SPA | http://localhost:5173 |
| MySQL | 127.0.0.1:3306 / db: `e_learning` |

## Tài khoản test

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@elearning.com | password |
| Admin | admin@elearning.com | password |
| Student | student@elearning.com | password |
