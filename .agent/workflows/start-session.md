---
description: "Prompt mở đầu mỗi buổi làm việc — context + tiến độ + bắt đầu task"
---

# Bắt đầu buổi làm việc

> Dùng workflow này mỗi khi mở VSCode bắt đầu session mới.

---

## Bước 1 — Đọc context project

Đọc file `QLCV/QLCV-FE.md` để biết tiến độ hiện tại (task nào đã tick ✅, task nào đang ⬜).

---

## Bước 2 — Xác nhận stack

```
E-Learning Marketplace Frontend
Backend: Laravel 12 API tại http://localhost:8000
Stack: Vue 3 + Vite, Vue Router 4, Pinia, Tailwind CSS v3, Axios
  - lucide-vue-next (icons), flowbite-vue (Client UI), TailAdmin (Admin UI)
  - vee-validate + zod (validation), vue-toastification (toast)
  - nprogress, @vueuse/core, @videojs-player/vue, vue3-carousel
  - vue3-apexcharts (Dashboard)
Stores: adminAuth.js | studentAuth.js | cart.js
API modules: authApi | courseApi | lessonApi | orderApi | uploadApi | paymentApi | progressApi | quizApi | dashboardApi
Utils: formatCurrency | formatDate | formatDuration
```

---

## Bước 3 — Xác định task hôm nay

User cho biết task muốn làm. Tìm workflow tương ứng:

| Task | Workflow |
|------|----------|
| Setup project F0 | `/setup-project` |
| Auth UI F1 | `/auth-ui` |
| Admin Layout | `/admin-layout` |
| Client Layout | `/client-layout` |
| Admin CRUD F2.1-2.3 | `/admin-crud` |
| Client Courses F2.4-2.5 | `/client-courses` |
| Admin Lessons F2.6 | `/admin-lessons` |
| LearnPage F2.7 | `/learn-page` |
| Cart + Checkout F3.1-3.2 | `/cart-checkout` |
| Payment + Profile F3.3-3.5 | `/payment-profile` |
| Advanced F4 | `/advanced-features` |
| Testing final | `/testing-final` |

---

## Bước 4 — Bắt đầu thực hiện

1. Nhắc lại context task
2. Đọc workflow tương ứng
3. Bắt đầu thực hiện tuần tự
