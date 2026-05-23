# Excel Export (Orders & Teacher Payouts) — Design Spec

## Goal

Cho phép Admin và Giảng viên xuất báo cáo Excel (.xlsx) cho đơn hàng, yêu cầu rút tiền, và thu nhập giảng viên — phục vụ kế toán, có lọc theo khoảng thời gian.

## Architecture

**Package:** `maatwebsite/excel` (cài vào `e-learning-backend`)

**3 Export classes:**
```
Modules/Payment/app/Exports/OrdersExport.php
Modules/Commission/app/Exports/PayoutsExport.php
Modules/Commission/app/Exports/TeacherEarningsExport.php
```
Mỗi class implement `FromQuery + WithHeadings + WithMapping` từ Maatwebsite.

**Controller** nhận query params `?from=&to=&status=` và trả về:
```php
return Excel::download(new OrdersExport($filters), 'don-hang_2026-05-01_2026-05-31.xlsx');
```

---

## API Endpoints

```
GET /api/v1/admin/orders/export?from=YYYY-MM-DD&to=YYYY-MM-DD&status=
GET /api/v1/admin/payouts/export?from=YYYY-MM-DD&to=YYYY-MM-DD&status=
GET /api/v1/admin/teacher-earnings/export?from=YYYY-MM-DD&to=YYYY-MM-DD&teacher_id=
GET /api/v1/my-earnings/export?from=YYYY-MM-DD&to=YYYY-MM-DD   ← teacher tự xuất
```

Tất cả đều dùng middleware `auth:admin` (3 route đầu) và `auth:api` (route cuối).  
Mỗi route kiểm tra permission tương ứng qua `$this->authorize()` hoặc middleware `permission:`.

---

## Permissions

| Permission | Gán cho role |
|-----------|-------------|
| `orders.export` | super-admin, admin |
| `payouts.export` | super-admin, admin |
| `teacher_earnings.export` | super-admin, admin, teacher |

Thêm vào `RolePermissionSeeder` (chạy lại seeder hoặc migration riêng).

---

## Cột dữ liệu

### 1. orders_export — `don-hang_{from}_{to}.xlsx`

1 row = 1 order item (nếu đơn có nhiều khóa học → nhiều dòng, group theo order_code):

| # | Mã đơn hàng | Học viên | Email | Khóa học | Tổng tiền (₫) | Giảm giá (₫) | Thanh toán (₫) | Phương thức | Trạng thái | Ngày thanh toán |
|---|------------|---------|-------|---------|-------------|------------|--------------|-----------|-----------|---------------|

Lọc theo `paid_at` (hoặc `created_at` nếu chưa paid).  
Trạng thái map: `paid` → "Đã thanh toán", `pending` → "Chờ thanh toán", `failed` → "Thất bại", `cancelled` → "Đã hủy", `refunded` → "Hoàn tiền".

### 2. payouts_export — `rut-tien_{from}_{to}.xlsx`

| # | Giảng viên | Email | Số tiền (₫) | Trạng thái | Ghi chú GV | Ghi chú Admin | Ngày xử lý | Ngày tạo |
|---|-----------|-------|------------|-----------|-----------|--------------|-----------|---------|

Lọc theo `created_at`.  
Trạng thái map: `pending` → "Chờ duyệt", `approved` → "Đã duyệt", `rejected` → "Từ chối", `paid` → "Đã thanh toán".

### 3. teacher_earnings_export — `thu-nhap_{from}_{to}.xlsx`

| # | Giảng viên* | Khóa học | Mã đơn hàng | Doanh thu (₫) | Tỷ lệ HH (%) | Thu nhập (₫) | Loại | Ngày |
|---|------------|---------|------------|--------------|-------------|-------------|-----|-----|

*Cột "Giảng viên" chỉ hiển thị khi Admin xuất. Teacher tự xuất thì bỏ cột này.  
Lọc theo `created_at`.  
Loại map: `credit` → "Thu nhập", `debit` → "Hoàn trả".

---

## Frontend

### Các trang có nút Export

| Trang | Route | Permission cần |
|-------|-------|---------------|
| Đơn hàng Admin | `/admin/orders` | `orders.export` |
| Yêu cầu rút tiền | `/admin/payouts` | `payouts.export` |
| Thu nhập GV (admin view) | `/admin/teacher-earnings` | `teacher_earnings.export` |
| Thu nhập của tôi (teacher) | `/teacher/earnings` | `teacher_earnings.export` |

### Export Modal

Bấm nút "Xuất Excel" → hiện modal:
- Input **Từ ngày** (date picker, default: đầu tháng hiện tại)
- Input **Đến ngày** (date picker, default: hôm nay)
- Select **Trạng thái** (tùy trang, optional)
- Nút **Xuất Excel** → gọi API → browser download file
- Nút disabled + spinner khi đang tải

### Tên file download
```
don-hang_2026-05-01_2026-05-31.xlsx
rut-tien_2026-05-01_2026-05-31.xlsx
thu-nhap_2026-05-01_2026-05-31.xlsx
```

### Component mới
```
src/components/common/ExportExcelModal.vue   ← dùng chung cho cả 3 trang
```

Props: `title`, `endpoint`, `extraParams` (object), `hasStatusFilter`.

---

## Luồng download

```
User bấm Xuất Excel
  → Modal chọn date range
  → Bấm xác nhận
  → Frontend gọi GET /api/v1/.../export?from=&to= với responseType: 'blob'
  → Tạo object URL → <a download> → click → file tải về
  → Modal đóng
```

Không dùng background job — sync download, đủ cho quy mô hiện tại.

---

## Route Registration (static trước param)

Thêm route export TRƯỚC route `{id}` để tránh bị match nhầm:

```php
Route::get('orders/export', [AdminOrderController::class, 'export']); // ← trước
Route::get('orders/{id}', [AdminOrderController::class, 'show']);     // ← sau
```

---

## Testing

- Feature test: `GET /admin/orders/export` với admin có `orders.export` → 200, Content-Type `application/vnd.openxmlformats...`
- Feature test: teacher không có `orders.export` → 403
- Feature test: teacher gọi `/my-earnings/export` → chỉ thấy dữ liệu của chính mình
- Feature test: filter date range hoạt động đúng (không trả về record ngoài khoảng)
