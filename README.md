# 🎨 E-Learning Marketplace — Frontend

> **Đồ án tốt nghiệp** — Khoa Khoa học Máy tính, Đại học Duy Tân  
> Sinh viên: **Phan Văn Thành** | GVHD: Trịnh Sử Trường Thi | 2026

---

## 📋 Thông tin dự án

| | |
|---|---|
| **Tên đề tài** | Xây dựng hệ thống nền tảng học tập trực tuyến (E-Learning Marketplace) tích hợp thanh toán trực tuyến |
| **Sinh viên** | Phan Văn Thành — MSSV: 28211102974 |
| **GVHD** | Trịnh Sử Trường Thi |
| **Thời gian** | 12/03/2026 – 15/05/2026 |

---

## 🛠️ Công nghệ sử dụng

- **Vue.js 3** + **Vite** + **TypeScript** (composables)
- **Vue Router 4** + **Pinia**
- **Tailwind CSS v3** (`darkMode: 'class'`) + **Flowbite Vue** (Client UI)
- **TailAdmin Vue** (Admin UI — đã tích hợp template: sidebar, header, dashboard, dark mode, icons)
- **Axios** — kết nối Backend API Laravel 12 tại `http://localhost:8000`
- **lucide-vue-next** + **TailAdmin SVG icons** — icon system
- **VeeValidate + Zod** — form validation
- **Video.js** — video player
- **vue3-apexcharts** — biểu đồ Dashboard
- **vue-toastification** — toast notification
- **NProgress** — loading bar khi navigate route
- **@vueuse/core** — composables (useDebounce, useLocalStorage...)

---

## ⚙️ Cài đặt

```bash
# 1. Clone repository
git clone https://github.com/<your-username>/elearning-frontend.git
cd elearning-frontend

# 2. Cài dependencies
npm install

# 3. Cấu hình môi trường
cp .env.example .env

# 4. Chạy dev server
npm run dev
```

Truy cập: `http://localhost:5173`

> **Yêu cầu:** Node.js >= 18.x | Backend API phải đang chạy tại `http://localhost:8000`

---

## 📁 Cấu trúc thư mục

```
src/
├── api/            # HTTP calls (Axios)
├── components/
│   ├── common/     # ThemeToggler, CommonGridShape
│   └── layout/     # TailAdmin: AppSidebar, AppHeader, ThemeProvider, SidebarProvider, header/*
├── composables/    # useSidebar.ts, useTheme.ts
├── icons/          # TailAdmin SVG icon components (~48 icons)
├── layouts/        # AdminLayout.vue (TailAdmin) | ClientLayout.vue (Flowbite)
├── pages/          # admin/ | client/ | auth/ | ForbiddenPage | NotFoundPage
├── plugins/        # axios.js (instance + interceptors) | nprogress.js
├── router/         # index.js (routes + guards)
├── stores/         # adminAuth.js | studentAuth.js | cart.js
└── utils/          # formatCurrency.js | formatDate.js | formatDuration.js
```

---

## 📦 Scripts

```bash
npm run dev      # Dev server
npm run build    # Build production
npm run lint     # Kiểm tra code
```

---

## 👤 Tác giả

**Phan Văn Thành** — 📧 phvanthanh06@gmail.com  
Sinh viên năm 4, Khoa Khoa học Máy tính, Đại học Duy Tân

## License

Dự án phát hành theo giấy phép [MIT](LICENSE).

Được thực hiện với mục đích học thuật — Đồ án tốt nghiệp Đại học Duy Tân, 2026.