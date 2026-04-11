# Git Workflow — E-Learning Marketplace

> Tài liệu này hướng dẫn cách làm việc với Git trong dự án KLTN.
> Áp dụng cho: thêm tính năng, fix bug, sync code, làm việc hàng ngày.

---

## Mục lục

1. [Cấu trúc repo và remotes](#1-cấu-trúc-repo-và-remotes)
2. [Quy tắc đặt tên branch](#2-quy-tắc-đặt-tên-branch)
3. [Quy tắc commit message](#3-quy-tắc-commit-message)
4. [Luồng làm việc hàng ngày](#4-luồng-làm-việc-hàng-ngày)
5. [Thêm tính năng mới](#5-thêm-tính-năng-mới)
6. [Fix bug](#6-fix-bug)
7. [Môi trường dev — khởi động nhanh](#7-môi-trường-dev--khởi-động-nhanh)
8. [Sync code từ repo riêng về CN41](#8-sync-code-từ-repo-riêng-về-cn41)
9. [Các lệnh hay dùng](#9-các-lệnh-hay-dùng)
10. [Xử lý tình huống khẩn cấp](#10-xử-lý-tình-huống-khẩn-cấp)

---

## 1. Cấu trúc repo và remotes

```
Repo chính (CN41):   git@github.com:KLTN-03-2026/CN41.git        → origin
Repo backend riêng:  https://github.com/ahryxx0602/e-learning-backend  → backend-origin
Repo frontend riêng: https://github.com/ahryxx0602/e-learning-frontend → frontend-origin
```

Kiểm tra remotes hiện tại:
```bash
git remote -v
```

Cấu trúc thư mục trong CN41:
```
e-learning/
├── e-learning-backend/    ← Laravel 11
├── e-learning-frontend/   ← Vue.js 3
├── .gitignore
├── README.md
└── GIT_WORKFLOW.md        ← file này
```

---

## 2. Quy tắc đặt tên branch

Chỉ có **1 branch chính**: `main` — luôn là bản ổn định.

Khi làm tính năng mới hoặc fix bug, tạo branch riêng theo pattern:

| Loại | Pattern | Ví dụ |
|------|---------|-------|
| Tính năng mới | `feature/ten-tinh-nang` | `feature/payment-vnpay` |
| Fix bug | `fix/mo-ta-bug` | `fix/thumbnail-not-loading` |
| Cải thiện UI/UX | `ui/ten-man-hinh` | `ui/course-detail-page` |
| Hotfix khẩn | `hotfix/mo-ta` | `hotfix/login-crash` |
| Thử nghiệm | `experiment/ten` | `experiment/ai-quiz` |
| Config/Tooling | `chore/mo-ta` | `chore/pre-commit-setup` |

Nguyên tắc:
- Dùng dấu `-` thay vì `_` hoặc dấu cách
- Tên ngắn gọn, mô tả đúng việc đang làm
- **Xóa branch sau khi merge xong** — không để branch rác tồn tại

---

## 3. Quy tắc commit message

Format chuẩn:
```
<type>(<scope>): <mô tả ngắn>
```

| Type | Dùng khi |
|------|---------|
| `feat` | Thêm tính năng mới |
| `fix` | Sửa bug |
| `ui` | Thay đổi giao diện, CSS |
| `refactor` | Tái cấu trúc code (không thêm feature, không fix bug) |
| `chore` | Config, dependency, script, không liên quan logic |
| `docs` | Cập nhật tài liệu |
| `test` | Thêm/sửa test |
| `perf` | Tối ưu hiệu năng |

Scope thường dùng trong project:
- `auth`, `course`, `lesson`, `section`, `category`, `teacher`, `student`
- `payment`, `upload`, `dashboard`
- `frontend`, `backend`, `api`

Ví dụ thực tế:
```
feat(payment): tích hợp VNPay cổng thanh toán
fix(course): sửa lỗi thumbnail không hiển thị
ui(course-detail): cải thiện layout sidebar sticky
refactor(lesson): tách LessonController thành service layer
chore(deps): cập nhật laravel/sanctum lên v4.3
feat(ai): thêm tính năng auto-quiz từ tài liệu PDF
```

---

## 4. Luồng làm việc hàng ngày

### Bắt đầu ngày làm việc

```bash
cd ~/DATN/e-learning

# 1. Đảm bảo đang ở main và code sạch
git checkout main
git status          # phải clean

# 2. Kéo code mới nhất từ GitHub (nếu có thay đổi từ máy khác)
git pull origin main

# 3. Khởi động môi trường dev (xem Mục 7)
```

### Kết thúc ngày làm việc

```bash
# Nếu đang làm dở trên feature branch:
git add .
git commit -m "wip: <mô tả công việc đang dở>"   # WIP = Work In Progress
git push origin <tên-branch>

# Hoặc dùng stash nếu không muốn commit wip:
git stash push -m "wip: <mô tả>"
```

---

## 5. Thêm tính năng mới

Ví dụ: thêm tính năng thanh toán VNPay.

```bash
# Bước 1: Tạo branch từ main
git checkout main
git checkout -b feature/payment-vnpay

# Bước 2: Làm việc, commit thường xuyên
# ... code ...
git add e-learning-backend/Modules/Payment/
git commit -m "feat(payment): tạo module Payment với VNPay integration"

# ... code tiếp ...
git add e-learning-frontend/src/pages/checkout/
git commit -m "ui(checkout): thêm trang thanh toán và xác nhận đơn hàng"

# Bước 3: Khi xong, merge về main
git checkout main
git merge feature/payment-vnpay

# Bước 4: Push lên cả 3 repo
git push-all

# Bước 5: Xóa branch đã dùng xong
git branch -d feature/payment-vnpay
```

**Tip:** Commit nhỏ và thường xuyên — mỗi commit nên là 1 đơn vị thay đổi có nghĩa, không phải 1 ngày code mới commit 1 lần.

---

## 6. Fix bug

### Bug nhỏ (fix nhanh dưới 30 phút)

Fix thẳng trên `main` nếu không ảnh hưởng feature đang làm:

```bash
git checkout main
# ... sửa bug ...
git add <file-đã-sửa>
git commit -m "fix(course): sửa lỗi category không lưu khi tạo mới"
git push origin main
```

### Bug lớn hoặc cần test kỹ

```bash
# Tạo branch fix riêng
git checkout -b fix/lesson-video-not-playing

# ... debug và sửa ...
git add .
git commit -m "fix(lesson): sửa video không play do thiếu CORS header"

# Merge về main khi chắc chắn đã fix
git checkout main
git merge fix/lesson-video-not-playing
git push origin main
git branch -d fix/lesson-video-not-playing
```

### Debug — xem commit nào gây ra bug

```bash
# Xem history của 1 file cụ thể
git log --oneline e-learning-backend/Modules/Course/app/Http/Controllers/CourseController.php

# Xem nội dung thay đổi tại 1 commit cụ thể
git show <commit-hash>

# So sánh file hiện tại với 3 commit trước
git diff HEAD~3 e-learning-backend/Modules/Course/app/Http/Controllers/CourseController.php
```

---

## 7. Môi trường dev — khởi động nhanh

Mở **2 terminal** WSL riêng biệt:

### Terminal 1 — Backend

```bash
cd ~/DATN/e-learning/e-learning-backend
php artisan serve
# → http://localhost:8000
```

### Terminal 2 — Frontend

```bash
cd ~/DATN/e-learning/e-learning-frontend
npm run dev
# → http://localhost:5173
```

### Reset database (khi cần làm mới hoàn toàn)

```bash
cd ~/DATN/e-learning/e-learning-backend
php artisan migrate:fresh --seed
php artisan storage:link
```

### Tài khoản test sau khi seed

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@elearning.com | password |
| Admin | admin@elearning.com | password |
| Student | student@elearning.com | password |

### Kiểm tra log lỗi backend

```bash
cd ~/DATN/e-learning/e-learning-backend
tail -f storage/logs/laravel.log
```

### Sau khi thêm Migration mới

```bash
php artisan migrate          # chỉ chạy migration mới
# KHÔNG dùng migrate:fresh khi không muốn mất dữ liệu
```

### Sau khi thêm Module mới (Nwidart)

```bash
php artisan module:enable <TênModule>
php artisan optimize:clear
```

---

## 8. Push code lên cả 3 repo

Dự án dùng **monorepo local** với 3 remote:

| Remote | Repo | Nội dung |
|--------|------|---------|
| `origin` | KLTN-03-2026/CN41 | Toàn bộ monorepo (repo nhóm) |
| `backend-origin` | ahryxx0602/e-learning-backend | Chỉ code BE (subtree split) |
| `frontend-origin` | ahryxx0602/e-learning-frontend | Chỉ code FE (subtree split) |

### Setup alias (chỉ cần làm 1 lần)

```bash
git config alias.push-all '!git push origin main && git push backend-origin $(git subtree split --prefix=e-learning-backend main):main --force && git push frontend-origin $(git subtree split --prefix=e-learning-frontend main):main --force'
```

### Push hàng ngày

Sau khi commit xong, chạy **1 lệnh duy nhất**:

```bash
git push-all
```

Lệnh này sẽ tự động:
1. Push toàn bộ monorepo lên `origin` (CN41)
2. Tách và push chỉ `e-learning-backend/` lên `backend-origin`
3. Tách và push chỉ `e-learning-frontend/` lên `frontend-origin`

> **Lưu ý:** `git push-all` dùng `--force` cho BE/FE solo repo — bình thường vì đây là repo cá nhân, chỉ mình bạn dùng.

### Branch `history` trên BE/FE solo

Mỗi repo solo có branch `history` chứa lịch sử commit từ tháng 3/2026 — dùng để chứng minh quá trình làm việc cho KLTN. **Không merge, không xóa branch này.**

---

## 9. Các lệnh hay dùng

### Xem trạng thái

```bash
git status                  # xem file nào đang thay đổi
git diff                    # xem nội dung thay đổi chưa stage
git diff --staged           # xem nội dung đã stage chuẩn bị commit
git log --oneline -10       # xem 10 commit gần nhất
git log --oneline --graph   # xem history dạng đồ thị
```

### Stage và commit

```bash
git add <file>              # stage file cụ thể (ưu tiên cách này)
git add e-learning-backend/ # stage toàn bộ thư mục backend
git add e-learning-frontend/src/pages/  # stage thư mục con cụ thể
git commit -m "feat: ..."   # commit với message

# Sửa commit message vừa tạo (CHƯA push)
git commit --amend -m "feat: message mới"
```

### Branch

```bash
git branch                  # xem danh sách branch
git checkout -b <tên>       # tạo và chuyển sang branch mới
git checkout main           # về main
git merge <tên-branch>      # merge branch vào branch hiện tại
git branch -d <tên>         # xóa branch đã merge
git branch -D <tên>         # xóa branch chưa merge (cẩn thận)
```

### Stash (lưu tạm khi cần chuyển branch gấp)

```bash
git stash push -m "wip: đang làm payment"   # lưu tạm
git stash list                               # xem danh sách stash
git stash pop                                # lấy lại stash mới nhất
git stash drop stash@{0}                    # xóa stash
```

### Undo (hoàn tác)

```bash
# Hủy thay đổi chưa stage (nguy hiểm — không khôi phục được)
git restore <file>

# Bỏ stage file (file vẫn giữ thay đổi)
git restore --staged <file>

# Quay về commit trước (giữ thay đổi trong working tree)
git reset HEAD~1

# Xem file ở commit cụ thể mà không thay đổi gì
git show <commit-hash>:<đường-dẫn-file>
```

### Push / Pull

```bash
git push-all                            # push lên cả 3 repo (origin + BE + FE)
git push origin main                    # push chỉ lên repo nhóm CN41
git pull origin main                    # kéo code mới về
git push origin <branch>               # push branch lên GitHub
```

---

## 10. Xử lý tình huống khẩn cấp

### Lỡ commit sai, chưa push

```bash
# Hoàn tác commit vừa tạo, giữ nguyên thay đổi
git reset HEAD~1

# Sửa rồi commit lại đúng
git add <file>
git commit -m "feat: ..."
```

### Lỡ xóa file cần thiết

```bash
# Khôi phục file từ commit trước đó
git checkout HEAD -- <đường-dẫn-file>

# Hoặc lấy từ commit cụ thể
git checkout <commit-hash> -- <đường-dẫn-file>
```

### Muốn xem code ở thời điểm commit cũ (không sửa gì)

```bash
# Xem file ở commit đó
git show <commit-hash>:<đường-dẫn-file>

# Hoặc checkout tạm để xem (detached HEAD — an toàn nếu không commit)
git checkout <commit-hash>
# Quay lại main:
git checkout main
```

### Database bị lỗi / migration conflict

```bash
cd ~/DATN/e-learning/e-learning-backend

# Reset hoàn toàn (mất dữ liệu — chỉ dùng trong dev)
php artisan migrate:fresh --seed

# Chỉ rollback migration cuối
php artisan migrate:rollback

# Xem trạng thái migration
php artisan migrate:status
```

### Push bị reject (remote có commit mới hơn)

```bash
# Kéo về trước rồi push lại
git pull origin main
# Nếu có conflict → resolve → commit → push
git push origin main
```

---

> **Nhớ:** `main` luôn phải là bản chạy được. Không commit code lỗi vào main.
> Deadline: **15/05/2026** — commit thường xuyên để có lịch sử rõ ràng cho báo cáo.
