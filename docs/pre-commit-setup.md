# Pre-commit Hook Setup

Tài liệu này mô tả cách hoạt động và mục đích của hệ thống pre-commit hook trong dự án.

## Mục đích

Tự động kiểm tra và format code **ngay tại local** trước khi commit, thay vì chờ CI/CD trên GitHub Actions phát hiện lỗi.

| Không có pre-commit | Có pre-commit |
|---|---|
| Commit code lỗi lint → CI fail → fix → push lại | Bị chặn ngay local, fix luôn |
| Format PHP/Vue mỗi người một kiểu | Tự động chuẩn hóa trước mỗi commit |
| Review PR mất thời gian vào style | Review chỉ tập trung vào logic |

---

## Các thành phần

### 1. Husky

Tool đăng ký git hook thông qua npm. Git hook thông thường nằm trong `.git/hooks/` — thư mục này **không được commit vào repo**, nên mỗi người clone về phải tự setup lại. Husky giải quyết bằng cách lưu hook vào `.husky/` (có thể commit) và tự cài vào `.git/hooks/` khi chạy `npm install`.

```
git commit
    ↓
.git/hooks/pre-commit  ← do Husky tạo tự động
    ↓
.husky/pre-commit      ← file được commit vào repo
    ↓
npx lint-staged
```

### 2. lint-staged

Chạy linter/formatter **chỉ trên các file đang được staged** (`git add`), không phải toàn bộ project. Giúp hook chạy nhanh dù project lớn.

Config trong `package.json` (root):

```json
"lint-staged": {
  "e-learning-frontend/**/*.{vue,ts,js}": [
    "oxlint --fix",
    "eslint --fix --cache",
    "prettier --write"
  ],
  "e-learning-backend/**/*.php": [
    "./vendor/bin/pint"
  ]
}
```

### 3. commit-msg hook (`.husky/commit-msg`)

Validate format commit message theo convention của dự án. Chạy **sau** pre-commit, trước khi commit được tạo.

**Format bắt buộc:**
```
<type>(<scope>): <description>
```

**Types hợp lệ:** `feat` · `fix` · `ui` · `refactor` · `chore` · `docs` · `test` · `perf`

**Scopes hợp lệ:** `auth` · `course` · `lesson` · `section` · `category` · `teacher` · `student` · `payment` · `upload` · `dashboard` · `frontend` · `backend` · `api`

**Ví dụ hợp lệ:**
```bash
feat(course): add course detail page
fix(auth): fix token not cleared on logout
ui(dashboard): improve sidebar layout
chore(deps): upgrade laravel/sanctum to v4.3
```

**Ví dụ bị chặn:**
```bash
"update code"          # thiếu type và scope
"feat: something"      # thiếu scope
"FEAT(course): ..."    # type phải viết thường
```

Khi sai format, hook hiển thị:
```
✖ Commit message không đúng format!

  Format: <type>(<scope>): <description>
  Ví dụ:  feat(course): add course detail page

  Types: feat | fix | ui | refactor | chore | docs | test | perf
  Scopes: auth | course | lesson | section | category | teacher
          student | payment | upload | dashboard | frontend | backend | api
```

### 4. Laravel Pint (`e-learning-backend/pint.json`)

PHP code formatter có sẵn trong Laravel 12. Tự động chuẩn hóa code PHP theo Laravel coding style.

```json
{
  "preset": "laravel",
  "rules": {
    "ordered_imports": true,
    "no_unused_imports": true
  }
}
```

---

## Luồng hoạt động

```
Bạn gõ: git commit -m "feat(course): add course detail page"
              ↓
         Husky kích hoạt .husky/pre-commit
              ↓
         lint-staged lọc file staged theo pattern
              ↓
    ┌─────────────────────────────────┐
    │  *.vue / *.ts / *.js  (FE)      │
    │  → oxlint --fix                 │
    │  → eslint --fix --cache         │
    │  → prettier --write             │
    │                                 │
    │  *.php  (BE)                    │
    │  → pint (Laravel formatter)     │
    └─────────────────────────────────┘
              ↓
    Có lỗi không tự fix được?
         ↓               ↓
        NO               YES
         ↓               ↓
    tiếp tục           BLOCKED
                   Bạn phải fix tay
                   rồi git add lại
              ↓
         Husky kích hoạt .husky/commit-msg
              ↓
         Validate format commit message
              ↓
    Đúng format?
         ↓               ↓
        YES              NO
         ↓               ↓
      commit           BLOCKED
      thành            Hiện hướng dẫn
      công             format đúng
```

---

## Cài đặt lại từ đầu (khi clone repo mới)

Vì Husky tự cài hook qua `prepare` script, chỉ cần:

```bash
# Tại root repo (e-learning/)
npm install
```

Lệnh này tự chạy `husky` và đăng ký cả `.husky/pre-commit` lẫn `.husky/commit-msg` vào `.git/hooks/`.

---

## Lưu ý Monorepo (quan trọng)

Vì `package.json` của lint-staged nằm ở **root repo**, khi lệnh chạy nó đang đứng ở `e-learning/` — không phải bên trong `e-learning-frontend/` hay `e-learning-backend/`.

**Đường dẫn Pint phải đầy đủ từ root:**
```json
"e-learning-backend/**/*.php": [
  "./e-learning-backend/vendor/bin/pint"  ✔
  // KHÔNG phải: "./vendor/bin/pint"      ✘
]
```

**Lệnh FE dùng `bash -c 'cd ...'`** vì oxlint/eslint cần đứng trong `e-learning-frontend/` để đọc `eslint.config.js` và `node_modules` đúng chỗ.

**Quyền thực thi (chmod)** — bắt buộc trên Linux/Mac/WSL. Nếu thiếu, Git bỏ qua hook mà không báo lỗi:
```bash
chmod +x .husky/pre-commit .husky/commit-msg
```

**Husky v9** không cần dòng `. "$(dirname -- "$0")/_/husky.sh"` — đó là cú pháp của Husky v8. File hook chỉ cần `#!/bin/sh` và lệnh thực thi.

---

## Bỏ qua hook (trường hợp khẩn cấp)

```bash
git commit -m "wip: ..." --no-verify
```

> Chỉ dùng khi thực sự cần thiết (VD: commit WIP cuối ngày). Không dùng thường xuyên.

---

## Tự động sinh CHANGELOG (tùy chọn)

Vì commit message đã được enforce đúng format `<type>(<scope>): <description>`, có thể dùng **standard-version** để tự động sinh `CHANGELOG.md` từ các commit `feat` và `fix`.

```bash
# Cài một lần
npm install --save-dev standard-version

# Chạy khi cần tạo release/báo cáo
npx standard-version --no-tag
```

Output sinh ra tự động:
```markdown
## [1.1.0] - 2026-04-11

### Features
* **course:** add course detail page (a1b2c3d)
* **auth:** implement student registration (e4f5g6h)

### Bug Fixes
* **auth:** fix token not cleared on logout (i7j8k9l)
```

Hữu ích khi viết báo cáo tiến độ hoặc demo cho giảng viên hướng dẫn.

---

## Cấu trúc file liên quan

```
e-learning/
├── .husky/
│   ├── pre-commit          ← chạy npx lint-staged (lint + format)
│   └── commit-msg          ← validate format commit message
├── package.json            ← config lint-staged + husky devDependencies
├── package-lock.json
└── e-learning-backend/
    └── pint.json           ← config Laravel Pint
```
