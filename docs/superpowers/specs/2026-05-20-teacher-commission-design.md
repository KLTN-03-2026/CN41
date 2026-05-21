# Teacher Commission System — Design Spec

**Date:** 2026-05-20
**Status:** Approved

## Overview

Hệ thống tính và quản lý hoa hồng cho giảng viên trên nền tảng e-learning. Khi học viên mua khóa học, một phần doanh thu được tự động ghi nhận vào tài khoản giảng viên. Giảng viên có thể yêu cầu rút tiền, Admin duyệt và đánh dấu đã thanh toán.

## Quyết định thiết kế

| # | Câu hỏi | Quyết định |
|---|---------|-----------|
| 1 | Mô hình hoa hồng | Tỷ lệ cố định toàn hệ thống (default: 70% giảng viên / 30% nền tảng) |
| 2 | Cơ chế chi | Tự động ghi nhận khi sale + giảng viên gửi yêu cầu rút, Admin duyệt |
| 3 | Portal giảng viên | Có — dashboard thu nhập riêng |
| 4 | Hoàn tiền | Tự động tạo bản ghi debit khi order bị refund |

## Kiến trúc

Module mới: `Modules/Commission/` (Nwidart). Hoàn toàn tách biệt, không sửa code Payment hiện có — chỉ thêm listener vào event `OrderPaid` đã có.

---

## Data Model

### Bảng `teacher_earnings` — Sổ cái hoa hồng

| Cột | Kiểu | Ghi chú |
|-----|------|---------|
| `id` | bigint PK | |
| `teacher_id` | FK → teachers | |
| `order_item_id` | FK → order_items, nullable | null khi điều chỉnh thủ công |
| `type` | enum(credit, debit) | credit = thu vào, debit = trừ ra (refund) |
| `amount` | decimal(12,2) | Luôn dương — type quyết định dấu khi SUM |
| `commission_rate` | decimal(5,2) | Snapshot tỷ lệ tại thời điểm giao dịch |
| `description` | string, nullable | "Hoa hồng từ: Laravel Cơ bản" |
| `timestamps` | created_at, updated_at | |

**Số dư giảng viên** = `SUM(amount WHERE type=credit) - SUM(amount WHERE type=debit) - SUM(pending/approved payouts)`

### Bảng `teacher_payouts` — Yêu cầu rút tiền

| Cột | Kiểu | Ghi chú |
|-----|------|---------|
| `id` | bigint PK | |
| `teacher_id` | FK → teachers | |
| `amount` | decimal(12,2) | Số tiền yêu cầu rút |
| `status` | enum(pending, approved, rejected, paid) | |
| `teacher_note` | text, nullable | Ghi chú từ giảng viên |
| `admin_note` | text, nullable | Ghi chú từ Admin khi xử lý |
| `processed_at` | timestamp, nullable | Thời điểm Admin xử lý |
| `timestamps` | created_at, updated_at | |

### Bảng `commission_settings` — Cấu hình tỷ lệ

| Cột | Kiểu | Ghi chú |
|-----|------|---------|
| `id` | bigint PK | 1 dòng duy nhất |
| `teacher_rate` | decimal(5,2) default 70.00 | % giảng viên nhận |
| `timestamps` | created_at, updated_at | |

Platform rate = `100 - teacher_rate`.

### Bổ sung vào `teachers`

Thêm 3 cột thông tin ngân hàng:
- `bank_name` — string, nullable
- `bank_account_number` — string, nullable
- `bank_account_name` — string, nullable

---

## Backend Flow

### Flow 1: Ghi nhận hoa hồng khi thanh toán thành công

```
VnpayService::handleIpn()
  → update Order(paid) + enrollStudent()        [đã có]
  → fire OrderPlaced event                      [đã có — tên thực tế]
      → CommissionListener::handle()            [mới]
          → CommissionService::recordEarnings($order)
              → lấy commission_rate từ CommissionSettings
              → loop order_items:
                  amount = final_price × rate / 100
                  insert teacher_earnings (type: credit)
```

### Flow 2: Đảo ngược hoa hồng khi hoàn tiền

```
Admin update order → status: refunded
  → fire OrderRefunded event                    [tạo mới trong Payment module]
      → CommissionListener::handleRefund()      [mới]
          → CommissionService::reverseEarnings($order)
              → loop order_items:
                  insert teacher_earnings (type: debit)
```

### Flow 3: Yêu cầu và duyệt rút tiền

```
Teacher POST /teacher/payouts
  → validate: amount ≤ available_balance
  → insert teacher_payouts (status: pending)

Admin PATCH /admin/payouts/{id}/approve  → status: approved
Admin PATCH /admin/payouts/{id}/reject   → status: rejected (số dư hoàn lại)
Admin PATCH /admin/payouts/{id}/mark-paid → status: paid
```

**Số dư khả dụng** = tổng credit - tổng debit - tổng (pending + approved payouts).

---

## API Endpoints

Tất cả prefix `/api/v1`.

### Admin (`auth:admin`)

| Method | URL | Mô tả |
|--------|-----|-------|
| GET | `/admin/commission-settings` | Xem tỷ lệ hiện tại |
| PATCH | `/admin/commission-settings` | Cập nhật tỷ lệ |
| GET | `/admin/payouts` | Danh sách yêu cầu rút (filter: status, teacher_id) |
| PATCH | `/admin/payouts/{id}/approve` | Duyệt yêu cầu |
| PATCH | `/admin/payouts/{id}/reject` | Từ chối yêu cầu |
| PATCH | `/admin/payouts/{id}/mark-paid` | Đánh dấu đã chuyển khoản |
| GET | `/admin/teacher-earnings` | Tổng hợp hoa hồng theo giảng viên |

### Giảng viên (`auth:admin`, role: teacher)

| Method | URL | Mô tả |
|--------|-----|-------|
| GET | `/teacher/earnings` | Số dư + lịch sử hoa hồng của tôi |
| GET | `/teacher/payouts` | Lịch sử yêu cầu rút của tôi |
| POST | `/teacher/payouts` | Gửi yêu cầu rút tiền mới |

---

## Frontend

### Trang Admin

| File | Route | Mô tả |
|------|-------|-------|
| `PayoutsPage.vue` | `/admin/payouts` | Danh sách yêu cầu rút, filter theo status, nút Duyệt/Từ chối/Đã thanh toán |
| `TeacherEarningsPage.vue` | `/admin/teacher-earnings` | Bảng tổng hợp: tổng kiếm, đã thanh toán, số dư, đang chờ — theo từng giảng viên |
| `CommissionSettingsPage.vue` | `/admin/commission-settings` | Input tỷ lệ, hiển thị phần nền tảng nhận tự động |

### Trang Giảng viên

| File | Route | Mô tả |
|------|-------|-------|
| `EarningsPage.vue` | `/teacher/earnings` | 3 card KPI (số dư, tổng kiếm, đang chờ) + bảng lịch sử + nút yêu cầu rút |

---

## Module Structure (`Modules/Commission/`)

```
Modules/Commission/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/CommissionSettingsController.php
│   │   │   ├── Admin/PayoutController.php
│   │   │   ├── Admin/TeacherEarningsController.php
│   │   │   └── Teacher/EarningsController.php
│   │   ├── Requests/
│   │   │   └── StorePayoutRequest.php
│   │   └── Resources/
│   │       ├── TeacherEarningResource.php
│   │       └── TeacherPayoutResource.php
│   ├── Models/
│   │   ├── TeacherEarning.php
│   │   ├── TeacherPayout.php
│   │   └── CommissionSetting.php
│   ├── Repositories/
│   │   ├── CommissionRepositoryInterface.php
│   │   └── CommissionRepository.php
│   ├── Services/
│   │   └── CommissionService.php
│   └── Listeners/
│       └── CommissionListener.php
├── database/migrations/
│   ├── create_teacher_earnings_table.php
│   ├── create_teacher_payouts_table.php
│   ├── create_commission_settings_table.php
│   └── add_bank_fields_to_teachers_table.php
└── routes/api.php
```

---

## Lưu ý Implementation

- `CommissionSetting` dùng pattern singleton (luôn chỉ có 1 dòng) — dùng `firstOrCreate` khi seed.
- Số dư tính tại query time (SUM) — không cache vào cột để tránh race condition.
- Khi validate payout request: `available_balance = credit - debit - pending_payouts - approved_payouts`.
- `CommissionListener` lắng nghe `OrderPlaced` (tên thực tế của event payment) và `OrderRefunded` (tạo mới). Đăng ký trong `CommissionServiceProvider::boot()` — không sửa `EventServiceProvider` của Payment module.
- `OrderRefunded` event cần tạo trong `Modules/Payment/app/Events/` và fire từ `OrderController` khi Admin update status → refunded.
- Giảng viên truy cập `/teacher/*` qua guard `admin` + middleware kiểm tra role `teacher` — thêm middleware `role:teacher` (Spatie).
- Route giảng viên phải đặt trước route admin để tránh collision nếu cùng prefix.
