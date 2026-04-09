---
description: "FE 0 — Setup Vue 3 Project (Vite + Tailwind + Pinia + Router + Axios + tất cả dependencies)"
---

# FE 0 — Setup Vue 3 Project

> **Phase F0** — Setup ban đầu cho E-Learning Marketplace Frontend.
> Backend API đã có ở `http://localhost:8000` (Laravel 12, CORS cho phép localhost:5173).

---

## Bước 1 — Tạo project Vue 3 + Vite

// turbo
```bash
npm create vue@latest ./ -- --router --pinia --eslint --prettier
```

> Chọn: TypeScript: **No**, JSX: **No**, Vue Router: **Yes**, Pinia: **Yes**, ESLint: **Yes**, Prettier: **Yes**

---

## Bước 2 — Cài dependencies

// turbo
```bash
npm install
```

// turbo
```bash
npm install axios lucide-vue-next flowbite-vue flowbite vue-toastification nprogress @vueuse/core vee-validate zod @vee-validate/zod @videojs-player/vue video.js vue3-carousel
```

// turbo
```bash
npm install -D tailwindcss postcss autoprefixer
```

// turbo
```bash
npx tailwindcss init -p
```

---

## Bước 3 — Cấu hình Tailwind (`tailwind.config.js`)

```js
export default {
  content: [
    './index.html',
    './src/**/*.{vue,js}',
    'node_modules/flowbite-vue/**/*.{js,jsx,ts,tsx,vue}',
    'node_modules/flowbite/**/*.{js,jsx,ts,tsx}'
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50:  '#eff6ff',
          100: '#dbeafe',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
        }
      }
    }
  },
  plugins: [],
}
```

---

## Bước 4 — Thêm Tailwind vào `src/assets/main.css`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer components {
  .btn-primary   { @apply bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50; }
  .btn-secondary { @apply bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
  .btn-danger    { @apply bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors; }
  .btn-sm-secondary { @apply text-xs px-2.5 py-1 border border-gray-300 rounded hover:bg-gray-50 transition-colors; }
  .btn-sm-danger    { @apply text-xs px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 rounded hover:bg-red-100 transition-colors; }

  .input-field { @apply w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent; }
  .input-error { @apply border-red-400 focus:ring-red-400; }
  .error-msg   { @apply text-xs text-red-500 mt-1; }

  .card        { @apply bg-white rounded-xl border border-gray-200 p-6; }
  .page-title  { @apply text-xl font-semibold text-gray-800 mb-6; }

  .badge-green  { @apply inline-flex text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700; }
  .badge-amber  { @apply inline-flex text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700; }
  .badge-red    { @apply inline-flex text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700; }
  .badge-gray   { @apply inline-flex text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600; }
}
```

---

## Bước 5 — Cấu hình Vite proxy (`vite.config.js`)

```js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) }
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      }
    }
  }
})
```

---

## Bước 6 — Biến môi trường

Tạo `.env`:
```env
VITE_APP_NAME="E-Learning Marketplace"
VITE_API_URL=/api/v1
VITE_FRONTEND_URL=http://localhost:5173
```

Tạo `.env.example` (giống `.env`, commit được):
```env
VITE_APP_NAME="E-Learning Marketplace"
VITE_API_URL=/api/v1
VITE_FRONTEND_URL=http://localhost:5173
```

> Kiểm tra `.gitignore` đã có `.env` — chỉ commit `.env.example`.

---

## Bước 7 — Tạo Axios instance (`src/plugins/axios.js`)

```js
import axios from 'axios'

const http = axios.create({
  baseURL: '/api/v1',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  timeout: 15000,
})

// Request interceptor — tự gắn token
http.interceptors.request.use((config) => {
  if (config.url?.startsWith('/admin')) {
    const token = localStorage.getItem('adminToken')
    if (token) config.headers.Authorization = `Bearer ${token}`
  } else {
    const token = localStorage.getItem('studentToken')
    if (token) config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor — xử lý lỗi chung
http.interceptors.response.use(
  (res) => res,
  (error) => {
    const status = error.response?.status
    const isAdminRoute = error.config?.url?.startsWith('/admin')
    if (status === 401) {
      if (isAdminRoute) {
        localStorage.removeItem('adminToken')
        window.location.href = '/admin/login'
      } else {
        localStorage.removeItem('studentToken')
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default http
```

---

## Bước 8 — Cấu hình NProgress (`src/plugins/nprogress.js`)

```js
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'

NProgress.configure({ showSpinner: false, speed: 400 })
export default NProgress
```

---

## Bước 9 — Tạo Pinia Stores

Tạo 3 file:
- `src/stores/adminAuth.js` — token, user, login(), logout(), fetchMe()
- `src/stores/studentAuth.js` — token, student, register(), login(), logout(), fetchMe()
- `src/stores/cart.js` — items (persist localStorage), addItem(), removeItem(), clear()

> Tham khảo chi tiết code tại `Promt-set-up/handbook.md` — Section 6 (Pinia).

---

## Bước 10 — Cấu hình Vue Router (`src/router/index.js`)

- `/admin/login` → AdminLoginPage (guest only)
- `/admin/*` → AdminLayout (requiresAuth: admin)
- `/` → ClientLayout (public)
- `/my-courses`, `/cart`, `/checkout`, `/profile` → requiresAuth: student
- Navigation guard: kiểm tra `adminToken` và `studentToken` từ localStorage
- NProgress: `beforeEach → start()`, `afterEach → done()`

> Tham khảo chi tiết code tại `Promt-set-up/handbook.md` — Section 5 (Vue Router).

---

## Bước 11 — Cấu hình main.js

```js
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

app.use(Toast, {
  position: 'top-right',
  timeout: 3000,
  closeOnClick: true,
})
```

---

## Bước 12 — Tạo Utils

- `src/utils/formatCurrency.js` — `Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' })`
- `src/utils/formatDate.js` — formatDate, formatDatetime, timeAgo
- `src/utils/formatDuration.js` — formatDuration(minutes), formatSeconds(s)

---

## Bước 13 — Tạo API files

Tạo `src/api/authApi.js` với đầy đủ hàm:
- adminLogin, adminLogout, adminMe
- studentRegister, studentLogin, studentLogout, studentMe
- forgotPassword, resetPassword

> Tham khảo chi tiết code tại `Promt-set-up/handbook.md` — Section 10.1.

---

## Bước 14 — Tạo cấu trúc thư mục

```
src/
├── api/          ← authApi.js (tạo thêm courseApi, lessonApi, orderApi... SAU)
├── components/
│   ├── common/   ← AppButton.vue, AppInput.vue, AppModal.vue, AppPagination.vue, AppSkeleton.vue, AppAlert.vue
│   ├── admin/    ← DataTable.vue, FormCard.vue, StatCard.vue
│   └── client/   ← CourseCard.vue, VideoPlayer.vue, LessonSidebar.vue
├── layouts/      ← AdminLayout.vue, ClientLayout.vue
├── pages/
│   ├── admin/    ← DashboardPage.vue (placeholder)
│   ├── client/   ← HomePage.vue (placeholder)
│   └── auth/     ← AdminLoginPage.vue, LoginPage.vue, RegisterPage.vue
├── plugins/      ← axios.js, nprogress.js
├── router/       ← index.js
├── stores/       ← adminAuth.js, studentAuth.js, cart.js
└── utils/        ← formatCurrency.js, formatDate.js, formatDuration.js
```

---

## Bước 15 — Kiểm tra

// turbo
```bash
npm run dev
```

Mở `http://localhost:5173` — kiểm tra trang hiện thi đúng, không lỗi console.

---

## ✅ Hoàn thành Phase F0

Sau khi xong bước này, các task F0.1 → F0.4 được đánh dấu ✅ trong `QLCV/QLCV-FE.md`.
