# Refactor Plan — E-Learning Frontend


---

## Kiến trúc hiện tại (sau refactor cấu trúc)
src/
├── assets/
├── components/
│   ├── admin/        # BulkActions, LessonsManager, SectionsLessonsManager, OrderDetailModal, LessonPreviewModal
│   ├── client/       # CourseCard
│   ├── common/       # ThemeToggler — sẽ thêm ConfirmModal
│   └── layout/       # AppSidebar, AppHeader, NotificationMenu, UserMenu
├── composables/      # useTheme, useSidebar — sẽ thêm 5 composable mới
├── constants/        # roles.ts, app.ts
├── icons/
├── layouts/          # AdminLayout, ClientLayout
├── pages/
│   ├── admin/        # 10 pages
│   ├── client/       # 11 pages
│   └── auth/         # 3 pages
├── plugins/          # axios.js
├── router/           # index.js
├── services/         # 9 service files (thay thế api/)
├── stores/           # adminAuth.store.ts, studentAuth.store.ts, cart.store.ts
├── types/            # common, auth, course, order, index
└── utils/            # formatCurrency, formatDate, formatDuration

---

## Vấn đề đã phát hiện

### Components cần tách
| File | Dòng | Vấn đề |
|------|------|--------|
| admin/CategoriesPage.vue | 1157 | Tree rendering + form + bulk select inline |
| admin/SectionsLessonsManager.vue | 1395 | 4+ API, 8+ vùng UI |
| admin/CoursesPage.vue | 813 | Bulk select + search + pagination inline |
| admin/CourseFormPage.vue | 551 | 3+ API, 5 vùng (tabs, upload, form) |
| client/CourseDetailPage.vue | 469 | 8 vùng UI |
| admin/LessonsManager.vue | 397 | 5 API calls, 4 vùng UI |
| admin/OrdersPage.vue | 276 | Debounce search inline |
| admin/BulkActions.vue | 265 | 6 modal inline |
| admin/LessonPreviewModal.vue | 266 | Logic render video/doc/text |
| admin/OrderDetailModal.vue | 224 | 2 API, 7 vùng UI |
| layout/AppSidebar.vue | 308 | Logic menu phức tạp |

### Patterns lặp lại cần composable
| Pattern | Xuất hiện ở |
|---------|------------|
| Pagination (currentPage, lastPage, fetchPage) | CoursesPage, MyOrdersPage, MyCoursesPage, admin/CoursesPage, admin/OrdersPage, LessonsManager |
| Search + Debounce (debounceTimer, 400ms) | CoursesPage, admin/CoursesPage, CategoriesPage, admin/OrdersPage |
| Loading + Error (loading=ref(false), try/catch/finally) | ~16 file |
| Delete Confirmation Modal (deleteTarget, inline HTML) | BulkActions, LessonsManager, SectionsLessonsManager, admin/OrdersPage, admin/CategoriesPage |
| Bulk Selection (selectedIds Set, isAllSelected, toggleSelect) | admin/CoursesPage, admin/CategoriesPage, SectionsLessonsManager |

---

## Kế hoạch thực hiện

### TUẦN 1 — Composables nền tảng (MUST HAVE)

#### Task 1.1 — usePagination
- File: `src/composables/usePagination.ts`
- Logic: currentPage, lastPage, perPage, pagination ref, setPage(page), nhận fetchFn callback
- Dùng ngay ở: CoursesPage, MyOrdersPage, MyCoursesPage
- Commit: `refactor(frontend): add usePagination composable`

#### Task 1.2 — useDebounceSearch
- File: `src/composables/useDebounceSearch.ts`
- Logic: query ref, debounce(fn, delay=400), cleanup clearTimeout on unmount
- Dùng ngay ở: client/CoursesPage
- Commit: `refactor(frontend): add useDebounceSearch composable`

#### Task 1.3 — Apply vào 3 client pages
- File sửa: CoursesPage.vue, MyOrdersPage.vue, MyCoursesPage.vue
- Commit: `refactor(frontend): apply usePagination + useDebounceSearch to client pages`

### TUẦN 2 — Admin pages lớn (MUST HAVE)

#### Task 2.1 — useDeleteConfirm + ConfirmModal
- File mới: `src/composables/useDeleteConfirm.ts`, `src/components/common/ConfirmModal.vue`
- Logic: target ref, isOpen, confirm(item, callback), cancel()
- Commit: `refactor(frontend): add useDeleteConfirm and ConfirmModal`

#### Task 2.2 — Refactor admin/CoursesPage.vue (813 → ~400 dòng)
- Áp dụng: usePagination, useDebounceSearch, useDeleteConfirm
- Không tách component, chỉ dùng composable
- Commit: `refactor(admin): apply composables to CoursesPage`

#### Task 2.3 — Refactor admin/OrdersPage.vue
- Áp dụng: useDebounceSearch, usePagination
- Commit: `refactor(admin): apply composables to OrdersPage`

### TUẦN 3 — File khổng lồ (MUST HAVE)

#### Task 3.1 — Tách admin/CategoriesPage.vue (1157 dòng)
- Tách: CategoryTreeNode.vue, CategoryForm.vue
- Commit: `refactor(admin): extract CategoryTreeNode and CategoryForm`

#### Task 3.2 — Tách admin/SectionsLessonsManager.vue (1395 dòng)
- Tách: SectionItem.vue, LessonItem.vue
- Commit: `refactor(admin): extract SectionItem and LessonItem`

### TUẦN 4 — Nice to have

#### Task 4.1 — Tách admin/CourseFormPage.vue
- Tách: CourseInfoForm.vue, ThumbnailUpload.vue
- Commit: `refactor(admin): extract CourseInfoForm and ThumbnailUpload`

#### Task 4.2 — Tách client/CourseDetailPage.vue
- Tách: CourseSyllabus.vue
- Commit: `refactor(client): extract CourseSyllabus`

#### Task 4.3 — useBulkSelect
- File: `src/composables/useBulkSelect.ts`
- Commit: `refactor(frontend): add useBulkSelect and extract ThumbnailUpload, CourseSyllabus`

### TUẦN 5 — Buffer
- Không làm refactor mới
- Fix bug nếu có, tập trung tính năng còn thiếu

---

## Composables đã tạo
- [x] usePagination
- [x] useDebounceSearch
- [x] useDeleteConfirm
- [x] useBulkSelect
- [x] useAsyncData
- [x] useFormErrors

## Components đã tách
- [x] common/ConfirmModal.vue
- [x] admin/CategoryTreeNode.vue
- [x] admin/CategoryForm.vue
- [ ] admin/SectionItem.vue (không cần thiết — coupling quá cao)
- [x] admin/LessonItem.vue
- [x] admin/ThumbnailUpload.vue (đã tách từ CourseFormPage)
- [x] admin/CourseInfoForm.vue (đã tách từ CourseFormPage)
- [x] client/CourseSyllabus.vue (nice to have)

---

## Commit history refactor
1. `refactor(frontend): thêm src/types/ và src/constants/`
2. `refactor(frontend): chuyển src/api/ sang src/services/ với TypeScript`
3. `refactor(frontend): migrate stores sang TypeScript, thêm STORAGE_KEYS`
4. `chore(frontend): xóa demo store counter.js`
5. `refactor(frontend): cập nhật toàn bộ import paths sang services/ và stores mới`
6. `chore(frontend): xóa src/api/ và stores .js cũ`
7. `refactor(frontend): add useDeleteConfirm and ConfirmModal`
8. `refactor(admin): apply composables to CoursesPage`
9. `refactor(admin): apply composables to OrdersPage`
10. `refactor(admin): extract CategoryTreeNode and CategoryForm`
11. `refactor(admin): extract LessonItem, apply composables to SectionsLessonsManager`
12. `refactor(frontend): add useFormErrors and useAsyncData composables`
13. `feat(refactor): split large components and extract reusable composables. Finish phase 1 and 2.`

---

## GIAI ĐOẠN 2: MODULAR ARCHITECTURE & CẮT GIẢM FILE > 300 DÒNG
*(Dựa trên phân tích codebase mới nhất)*

### TUẦN 6 — Xử lý triệt để God Objects

#### Task 6.1 — Tách admin/SectionsLessonsManager.vue (~1300 dòng)
- [ ] Tách `LessonFormModal.vue`: Chuyển 350 dòng modal form + file upload video/tài liệu ra component riêng.
- [ ] Tách `SectionFormModal.vue`: Chuyển 150 dòng modal form thêm chương ra component riêng.
- [ ] Thừa hưởng: `useBulkSelect` (thay thế vòng lặp tick chọn), `useFormErrors` (thay thế for-in báo lỗi API).
- Mục tiêu: `SectionsLessonsManager.vue` giảm xuống còn ~500 dòng.

#### Task 6.2 — Tách client/LearnPage.vue (~1800 dòng)
- [ ] Tách `LearnSidebar.vue`: Cột trái hiển thị tiến độ và chương bài học.
- [ ] Tách `LearnVideoPlayer.vue`: Gói thẻ `<video>` và logic cập nhật progress.
- [ ] Tách `LearnDocumentViewer.vue`: Vùng hiển thị iframe trực tiếp tài liệu PDF.
- Mục tiêu: `LearnPage.vue` đóng đóng vai trò bố cục (layout) và load dữ liệu API.

### TUẦN 7 — Đồng bộ Cấu trúc Modular Monolith

Kiến trúc hiện tại rẽ nhánh theo Layer ngang (`pages/admin`, `pages/client`, `components/admin`, `services/...`). Để đồng bộ với kiến trúc Modular Monolith của Laravel Backend, ta sẽ gom chúng thành các Khối tính năng (Modules).

#### Task 7.1 — Cấu trúc lại thư mục cốt lõi
- [ ] Đổi tên `src/pages` thành `src/views`
- [ ] Di chuyển `src/icons` vào trong `src/components/icons`

#### Task 7.2 — Tạo và Di chuyển vào thư mục `src/modules`
- [ ] Tạo module **Auth**: Gom `src/views/auth/*`, `adminAuth.store.ts`, `studentAuth.store.ts` vào `src/modules/auth/`.
- [ ] Tạo module **Courses**: Gom dịch vụ `course.service.ts`, các `views` danh sách/tạo mới khóa học, các `components` liên quan.
- [ ] Tạo module **Learning**: Gom khu vực Học tập (`LearnPage` và các con của nó), `lesson.service.ts`.
- [ ] Sửa lại hàng loạt đường dẫn import `@/...`.

---

*File này được tạo tự động từ quá trình review và lên kế hoạch refactor.*
*Cập nhật checkbox khi hoàn thành từng task.*
