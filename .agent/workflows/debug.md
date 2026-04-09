---
description: "Debug lỗi frontend — phân tích và fix"
---

# Debug lỗi Frontend

> Dùng khi gặp lỗi trong quá trình phát triển.

---

## Bước 1 — Thu thập thông tin

Cần biết:
1. **Task đang làm:** tên task (F0.1, F1.1, ...)
2. **Error message:** toàn bộ lỗi từ console/terminal
3. **File liên quan:** tên file nếu biết
4. **Đã thử:** những gì đã thử

---

## Bước 2 — Phân loại lỗi

| Loại | Cách xử lý |
|------|------------|
| Build error (Vite) | Kiểm tra import, syntax, config |
| Runtime error (Vue) | Kiểm tra template, reactive data, lifecycle |
| API error (Axios) | Kiểm tra endpoint, token, CORS, response format |
| Style error (Tailwind) | Kiểm tra class names, config, purge |
| Router error | Kiểm tra route config, guards, lazy loading |

---

## Bước 3 — Fix

Stack context:
```
Vue 3 + Vite + Tailwind + Pinia + Vue Router + Axios
lucide-vue-next, flowbite-vue, vee-validate+zod, vue-toastification
```

Phân tích lỗi và đưa ra cách fix cụ thể.

---

## Bước 4 — Verify

Sau khi fix:
// turbo
```bash
npm run dev
```

Kiểm tra lỗi đã hết trong console.
