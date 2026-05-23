# Class Diagrams & ER Diagrams Documentation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tạo tài liệu Sơ đồ lớp (Class Diagram) và Biểu đồ quan hệ (ERD) cho toàn bộ hệ thống e-learning, phục vụ luận văn tốt nghiệp.

**Architecture:** 13 task — 1 task setup, 6 task ERD (5 domain + 1 full), 6 task Class Diagram (5 domain + 1 full). Mỗi task tạo một file Markdown độc lập chứa Mermaid diagram. Domain chia theo: Auth/Users, Course/Learning, Quiz, Payment, Content/Posts.

**Tech Stack:** Mermaid (erDiagram + classDiagram), Markdown, có thể export sang PNG qua mermaid.live hoặc VS Code extension.

---

## Cấu trúc Files

| File | Nội dung |
|------|----------|
| `docs/diagrams/README.md` | Index tất cả sơ đồ |
| `docs/diagrams/erd-auth.md` | ERD: users, students, teachers, tokens, roles, permissions |
| `docs/diagrams/erd-course-learning.md` | ERD: courses, categories, sections, lessons, lesson_progress, media_files |
| `docs/diagrams/erd-quiz.md` | ERD: quizzes, quiz_questions, quiz_attempts, quiz_generation_jobs |
| `docs/diagrams/erd-payment.md` | ERD: orders, order_items, transactions, coupons |
| `docs/diagrams/erd-content.md` | ERD: posts, post_categories, tags, post_tag, post_comments |
| `docs/diagrams/erd-full.md` | ERD tổng hợp toàn bộ hệ thống |
| `docs/diagrams/class-auth.md` | Class Diagram: User, Student, Teachers + auth traits |
| `docs/diagrams/class-course-learning.md` | Class Diagram: Course, Category, Section, Lesson, LessonProgress, MediaFile |
| `docs/diagrams/class-quiz.md` | Class Diagram: Quiz, QuizQuestion, QuizAttempt, QuizGenerationJob |
| `docs/diagrams/class-payment.md` | Class Diagram: Order, OrderItem, Transaction, Coupon |
| `docs/diagrams/class-content.md` | Class Diagram: Post, PostCategory, Tag, PostComment |
| `docs/diagrams/class-full.md` | Class Diagram tổng hợp toàn bộ |

---

## Task 1: Setup — Tạo thư mục và file README index

**Files:**
- Create: `docs/diagrams/README.md`

- [ ] **Bước 1: Tạo file README index**

Tạo file `docs/diagrams/README.md` với nội dung sau:

```markdown
# Tài liệu Sơ đồ Hệ thống E-Learning

## Biểu đồ Quan hệ (ERD)

| Sơ đồ | Mô tả | Link |
|-------|-------|------|
| Auth & Users | users, students, teachers, roles, permissions | [erd-auth.md](./erd-auth.md) |
| Course & Learning | courses, categories, sections, lessons, progress | [erd-course-learning.md](./erd-course-learning.md) |
| Quiz | quizzes, questions, attempts, AI jobs | [erd-quiz.md](./erd-quiz.md) |
| Payment | orders, items, transactions, coupons | [erd-payment.md](./erd-payment.md) |
| Content/Posts | posts, categories, tags, comments | [erd-content.md](./erd-content.md) |
| **Full ERD** | Toàn bộ hệ thống | [erd-full.md](./erd-full.md) |

## Sơ đồ Lớp (Class Diagram)

| Sơ đồ | Mô tả | Link |
|-------|-------|------|
| Auth & Users | User, Student, Teachers | [class-auth.md](./class-auth.md) |
| Course & Learning | Course, Category, Section, Lesson, MediaFile | [class-course-learning.md](./class-course-learning.md) |
| Quiz | Quiz, QuizQuestion, QuizAttempt | [class-quiz.md](./class-quiz.md) |
| Payment | Order, OrderItem, Transaction, Coupon | [class-payment.md](./class-payment.md) |
| Content/Posts | Post, PostCategory, Tag, PostComment | [class-content.md](./class-content.md) |
| **Full Class Diagram** | Toàn bộ hệ thống | [class-full.md](./class-full.md) |

## Hướng dẫn render

- **GitHub/GitLab**: Tự động render Mermaid trong Markdown
- **VS Code**: Cài extension "Markdown Preview Mermaid Support"
- **Export PNG**: Dán code vào [mermaid.live](https://mermaid.live) → Download SVG/PNG
- **PlantUML alternative**: Dùng [plantuml.com](https://plantuml.com) nếu cần UML chuẩn hơn
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/README.md
git commit -m "docs: add diagrams directory with README index"
```

---

## Task 2: ERD — Auth & Users Domain

**Files:**
- Create: `docs/diagrams/erd-auth.md`

**Bảng trong domain này:** `users`, `students`, `teachers`, `student_email_verifications`, `personal_access_tokens`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

- [ ] **Bước 1: Tạo file `docs/diagrams/erd-auth.md`**

```markdown
# ERD — Auth & Users Domain

Bao gồm: users (admin), students, teachers, xác thực email, Sanctum tokens, Spatie RBAC.

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        varchar avatar
        tinyint status "0=inactive 1=active"
        timestamp email_verified_at
        varchar remember_token
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    students {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        varchar avatar
        date date_of_birth
        timestamp email_verified_at
        varchar remember_token
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    teachers {
        bigint id PK
        bigint user_id FK "nullable - liên kết tài khoản"
        varchar name
        date date_of_birth
        varchar slug UK
        text description
        float exp "năm kinh nghiệm"
        varchar image
        tinyint status "0=inactive 1=active"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    student_email_verifications {
        bigint id PK
        varchar email
        varchar token UK
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    personal_access_tokens {
        bigint id PK
        varchar tokenable_type "App\\Models\\User hoặc Student"
        bigint tokenable_id
        varchar name "admin-token hoặc student-token"
        varchar token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    roles {
        bigint id PK
        varchar name "super-admin | admin | teacher"
        varchar guard_name "admin"
        timestamp created_at
        timestamp updated_at
    }

    permissions {
        bigint id PK
        varchar name "users.view | courses.create | ..."
        varchar guard_name "admin"
        timestamp created_at
        timestamp updated_at
    }

    model_has_roles {
        bigint role_id FK
        varchar model_type "App\\Models\\User"
        bigint model_id FK
    }

    model_has_permissions {
        bigint permission_id FK
        varchar model_type
        bigint model_id FK
    }

    role_has_permissions {
        bigint permission_id FK
        bigint role_id FK
    }

    users ||--o| teachers : "có hồ sơ giảng viên"
    teachers }o--|| users : "thuộc về tài khoản"
    users ||--o{ model_has_roles : "được gán vai trò"
    roles ||--o{ model_has_roles : "vai trò được gán"
    roles ||--o{ role_has_permissions : "có quyền"
    permissions ||--o{ role_has_permissions : "quyền thuộc vai trò"
    permissions ||--o{ model_has_permissions : "quyền trực tiếp"
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/erd-auth.md
git commit -m "docs: add ERD for auth and users domain"
```

---

## Task 3: ERD — Course & Learning Domain

**Files:**
- Create: `docs/diagrams/erd-course-learning.md`

**Bảng trong domain này:** `teachers` (stub), `students` (stub), `courses`, `categories`, `categories_courses`, `students_course`, `sections`, `lessons`, `lesson_progress`, `media_files`

- [ ] **Bước 1: Tạo file `docs/diagrams/erd-course-learning.md`**

```markdown
# ERD — Course & Learning Domain

Bao gồm: courses, categories (nested set), sections, lessons, tiến độ học tập, media files.
Teachers và Students được đưa vào dưới dạng stub — xem định nghĩa đầy đủ ở [erd-auth.md](./erd-auth.md).

```mermaid
erDiagram
    teachers {
        bigint id PK
        varchar name
    }

    students {
        bigint id PK
        varchar name
    }

    courses {
        bigint id PK
        bigint teacher_id FK
        varchar name
        varchar slug UK
        text description
        varchar thumbnail
        decimal price
        decimal sale_price
        enum level "beginner|intermediate|advanced"
        int total_lessons
        int total_students
        float rating
        tinyint status "0=draft 1=published"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    categories {
        bigint id PK
        varchar name
        varchar slug UK
        text description
        varchar icon
        tinyint status
        int order
        int _lft "Nested Set left"
        int _rgt "Nested Set right"
        bigint parent_id FK "nullable - tự tham chiếu"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    categories_courses {
        bigint course_id FK
        bigint category_id FK
    }

    students_course {
        bigint id PK
        bigint student_id FK
        bigint course_id FK
        timestamp enrolled_at
        timestamp created_at
        timestamp updated_at
    }

    sections {
        bigint id PK
        bigint course_id FK
        varchar title
        text description
        int order
        tinyint status
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    lessons {
        bigint id PK
        bigint course_id FK
        bigint section_id FK
        varchar title
        varchar slug UK
        text description
        enum type "video|document|text|quiz"
        text content
        bigint video_id FK "nullable - FK media_files"
        bigint document_id FK "nullable - FK media_files"
        int order
        boolean is_preview
        int duration "giây"
        tinyint status
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    media_files {
        bigint id PK
        varchar disk "local | s3"
        enum type "video|document|image"
        varchar original_name
        varchar path
        varchar url
        varchar mime_type
        int size "bytes"
        enum status "pending|ready|orphan"
        int reference_count
        int duration "giây - chỉ video"
        int width "chỉ video/image"
        int height "chỉ video/image"
        int bitrate "chỉ video"
        varchar codec "chỉ video"
        bigint uploaded_by FK "nullable - FK users"
        timestamp created_at
        timestamp updated_at
    }

    lesson_progress {
        bigint id PK
        bigint student_id FK
        bigint lesson_id FK
        bigint course_id FK
        boolean is_completed
        int watched_seconds
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }

    teachers ||--o{ courses : "dạy"
    courses }o--o{ categories : "thuộc danh mục"
    categories_courses }|--|| courses : ""
    categories_courses }|--|| categories : ""
    categories ||--o| categories : "danh mục cha"
    courses }o--o{ students : "học viên đăng ký"
    students_course }|--|| courses : ""
    students_course }|--|| students : ""
    courses ||--|{ sections : "có chương"
    sections ||--|{ lessons : "có bài học"
    courses ||--o{ lessons : "có bài học (qua sections)"
    lessons o|--o| media_files : "video bài học"
    lessons o|--o| media_files : "tài liệu bài học"
    students ||--o{ lesson_progress : "tiến độ học"
    lessons ||--o{ lesson_progress : "được theo dõi"
    courses ||--o{ lesson_progress : "tiến độ trong khoá"
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/erd-course-learning.md
git commit -m "docs: add ERD for course and learning domain"
```

---

## Task 4: ERD — Quiz Domain

**Files:**
- Create: `docs/diagrams/erd-quiz.md`

**Bảng trong domain này:** `lessons` (stub), `students` (stub), `quizzes`, `quiz_questions`, `quiz_attempts`, `quiz_generation_jobs`

- [ ] **Bước 1: Tạo file `docs/diagrams/erd-quiz.md`**

```markdown
# ERD — Quiz Domain

Bao gồm: quizzes gắn với lessons, câu hỏi trắc nghiệm, lịch sử làm bài, job AI tạo câu hỏi tự động.

```mermaid
erDiagram
    lessons {
        bigint id PK
        varchar title
        enum type "video|document|text|quiz"
    }

    students {
        bigint id PK
        varchar name
    }

    quizzes {
        bigint id PK
        bigint lesson_id FK
        varchar title
        text description
        int max_attempts "0 = không giới hạn"
        int time_limit "phút - 0 = không giới hạn"
        tinyint status "0=inactive 1=active"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    quiz_questions {
        bigint id PK
        bigint quiz_id FK
        text question
        varchar option_a
        varchar option_b
        varchar option_c
        varchar option_d
        enum correct_option "A|B|C|D"
        int order
        timestamp created_at
        timestamp updated_at
    }

    quiz_attempts {
        bigint id PK
        bigint quiz_id FK
        bigint student_id FK
        int score "điểm đạt được"
        int total_questions "tổng số câu"
        json answers "mảng đáp án đã chọn"
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }

    quiz_generation_jobs {
        bigint id PK
        bigint lesson_id FK
        enum status "pending|processing|done|failed"
        json payload "tham số gửi lên AI"
        json result "kết quả từ AI"
        text error "thông báo lỗi nếu failed"
        timestamp created_at
        timestamp updated_at
    }

    lessons ||--o| quizzes : "có quiz"
    quizzes ||--|{ quiz_questions : "gồm câu hỏi"
    quizzes ||--o{ quiz_attempts : "lịch sử làm bài"
    students ||--o{ quiz_attempts : "làm bài"
    lessons ||--o{ quiz_generation_jobs : "job tạo quiz AI"
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/erd-quiz.md
git commit -m "docs: add ERD for quiz domain"
```

---

## Task 5: ERD — Payment Domain

**Files:**
- Create: `docs/diagrams/erd-payment.md`

**Bảng trong domain này:** `students` (stub), `courses` (stub), `orders`, `order_items`, `transactions`, `coupons`

- [ ] **Bước 1: Tạo file `docs/diagrams/erd-payment.md`**

```markdown
# ERD — Payment Domain

Bao gồm: đơn hàng, chi tiết đơn hàng, giao dịch VNPAY, mã giảm giá.

```mermaid
erDiagram
    students {
        bigint id PK
        varchar name
        varchar email
    }

    courses {
        bigint id PK
        varchar name
        decimal price
        decimal sale_price
    }

    coupons {
        bigint id PK
        varchar code UK
        enum type "fixed|percentage"
        decimal value "giá trị giảm"
        decimal min_order_value "giá trị đơn hàng tối thiểu"
        decimal max_discount "giảm tối đa (cho percentage)"
        int usage_limit "0 = không giới hạn"
        int used_count
        timestamp start_date
        timestamp end_date
        tinyint status "0=inactive 1=active"
        text description
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    orders {
        bigint id PK
        varchar order_code UK
        bigint student_id FK
        decimal subtotal "tổng trước giảm giá"
        decimal discount_amount "số tiền giảm"
        decimal total_amount "tổng sau giảm giá"
        varchar coupon_code "lưu lại mã đã dùng"
        enum status "pending|paid|failed|cancelled|refunded"
        varchar payment_method "vnpay|..."
        text note
        timestamp paid_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    order_items {
        bigint id PK
        bigint order_id FK
        bigint course_id FK
        decimal price "giá gốc lúc mua"
        decimal sale_price "giá sale lúc mua"
        decimal final_price "giá thực tế đã thanh toán"
        timestamp created_at
        timestamp updated_at
    }

    transactions {
        bigint id PK
        bigint order_id FK
        varchar gateway "vnpay"
        varchar transaction_code "mã giao dịch từ cổng"
        varchar bank_code "mã ngân hàng"
        varchar card_type "ATM | QRCODE | ..."
        decimal amount
        enum status "pending|success|failed"
        json gateway_response "toàn bộ response từ VNPAY"
        varchar response_code "mã kết quả VNPAY"
        timestamp paid_at
        timestamp created_at
        timestamp updated_at
    }

    students ||--o{ orders : "đặt hàng"
    orders ||--|{ order_items : "chứa"
    order_items }|--|| courses : "khoá học mua"
    orders ||--o{ transactions : "giao dịch thanh toán"
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/erd-payment.md
git commit -m "docs: add ERD for payment domain"
```

---

## Task 6: ERD — Content/Posts Domain

**Files:**
- Create: `docs/diagrams/erd-content.md`

**Bảng trong domain này:** `users` (stub), `students` (stub), `posts`, `post_categories`, `tags`, `post_tag`, `post_comments`

- [ ] **Bước 1: Tạo file `docs/diagrams/erd-content.md`**

```markdown
# ERD — Content & Posts Domain

Bao gồm: bài viết blog, danh mục bài viết, tags, bình luận (threaded).

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name "tác giả bài viết"
    }

    students {
        bigint id PK
        varchar name "người bình luận"
    }

    post_categories {
        bigint id PK
        varchar name
        varchar slug UK
        text description
        timestamp created_at
        timestamp updated_at
    }

    tags {
        bigint id PK
        varchar name
        varchar slug UK
        timestamp created_at
        timestamp updated_at
    }

    posts {
        bigint id PK
        varchar title
        varchar slug UK
        text content
        varchar thumbnail
        bigint author_id FK "FK users"
        bigint post_category_id FK "nullable"
        boolean is_published
        timestamp published_at
        int views
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    post_tag {
        bigint id PK
        bigint post_id FK
        bigint tag_id FK
        timestamp created_at
        timestamp updated_at
    }

    post_comments {
        bigint id PK
        bigint post_id FK
        bigint user_id "ID của user hoặc student"
        varchar user_type "App\\Models\\User hoặc Student"
        text content
        bigint parent_id FK "nullable - comment cha"
        boolean is_approved
        timestamp created_at
        timestamp updated_at
    }

    users ||--o{ posts : "viết bài"
    post_categories ||--o{ posts : "phân loại"
    posts }o--o{ tags : "gắn nhãn"
    post_tag }|--|| posts : ""
    post_tag }|--|| tags : ""
    posts ||--o{ post_comments : "có bình luận"
    users ||--o{ post_comments : "bình luận (admin)"
    students ||--o{ post_comments : "bình luận (học viên)"
    post_comments ||--o{ post_comments : "trả lời (threaded)"
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/erd-content.md
git commit -m "docs: add ERD for content and posts domain"
```

---

## Task 7: ERD — Full Combined

**Files:**
- Create: `docs/diagrams/erd-full.md`

- [ ] **Bước 1: Tạo file `docs/diagrams/erd-full.md`**

```markdown
# ERD — Full System (Toàn bộ hệ thống)

> Gợi ý: Mở bằng [mermaid.live](https://mermaid.live) để zoom/export. Diagram này lớn — dùng các file domain riêng để dễ đọc hơn.

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        varchar avatar
        tinyint status
        timestamp deleted_at
    }

    students {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        varchar avatar
        date date_of_birth
        timestamp email_verified_at
        timestamp deleted_at
    }

    teachers {
        bigint id PK
        bigint user_id FK
        varchar name
        varchar slug UK
        float exp
        tinyint status
        timestamp deleted_at
    }

    student_email_verifications {
        bigint id PK
        varchar email
        varchar token UK
        timestamp expires_at
    }

    roles {
        bigint id PK
        varchar name
        varchar guard_name
    }

    permissions {
        bigint id PK
        varchar name
        varchar guard_name
    }

    model_has_roles {
        bigint role_id FK
        varchar model_type
        bigint model_id
    }

    role_has_permissions {
        bigint permission_id FK
        bigint role_id FK
    }

    courses {
        bigint id PK
        bigint teacher_id FK
        varchar name
        varchar slug UK
        decimal price
        decimal sale_price
        enum level "beginner|intermediate|advanced"
        int total_students
        float rating
        tinyint status
        timestamp deleted_at
    }

    categories {
        bigint id PK
        varchar name
        varchar slug UK
        tinyint status
        int _lft
        int _rgt
        bigint parent_id FK
        timestamp deleted_at
    }

    categories_courses {
        bigint course_id FK
        bigint category_id FK
    }

    students_course {
        bigint id PK
        bigint student_id FK
        bigint course_id FK
        timestamp enrolled_at
    }

    sections {
        bigint id PK
        bigint course_id FK
        varchar title
        int order
        timestamp deleted_at
    }

    lessons {
        bigint id PK
        bigint course_id FK
        bigint section_id FK
        varchar title
        varchar slug UK
        enum type "video|document|text|quiz"
        bigint video_id FK
        bigint document_id FK
        int order
        boolean is_preview
        timestamp deleted_at
    }

    lesson_progress {
        bigint id PK
        bigint student_id FK
        bigint lesson_id FK
        bigint course_id FK
        boolean is_completed
        int watched_seconds
        timestamp completed_at
    }

    media_files {
        bigint id PK
        enum type "video|document|image"
        varchar url
        int size
        enum status "pending|ready|orphan"
        bigint uploaded_by FK
    }

    quizzes {
        bigint id PK
        bigint lesson_id FK
        varchar title
        int max_attempts
        int time_limit
        tinyint status
        timestamp deleted_at
    }

    quiz_questions {
        bigint id PK
        bigint quiz_id FK
        text question
        varchar option_a
        varchar option_b
        varchar option_c
        varchar option_d
        enum correct_option "A|B|C|D"
        int order
    }

    quiz_attempts {
        bigint id PK
        bigint quiz_id FK
        bigint student_id FK
        int score
        int total_questions
        json answers
        timestamp completed_at
    }

    quiz_generation_jobs {
        bigint id PK
        bigint lesson_id FK
        enum status "pending|processing|done|failed"
        json result
        text error
    }

    orders {
        bigint id PK
        varchar order_code UK
        bigint student_id FK
        decimal subtotal
        decimal discount_amount
        decimal total_amount
        varchar coupon_code
        enum status "pending|paid|failed|cancelled|refunded"
        timestamp paid_at
        timestamp deleted_at
    }

    order_items {
        bigint id PK
        bigint order_id FK
        bigint course_id FK
        decimal final_price
    }

    transactions {
        bigint id PK
        bigint order_id FK
        varchar gateway
        varchar transaction_code
        decimal amount
        enum status "pending|success|failed"
        json gateway_response
        timestamp paid_at
    }

    coupons {
        bigint id PK
        varchar code UK
        enum type "fixed|percentage"
        decimal value
        int usage_limit
        int used_count
        tinyint status
        timestamp deleted_at
    }

    posts {
        bigint id PK
        varchar title
        varchar slug UK
        text content
        bigint author_id FK
        bigint post_category_id FK
        boolean is_published
        int views
        timestamp deleted_at
    }

    post_categories {
        bigint id PK
        varchar name
        varchar slug UK
    }

    tags {
        bigint id PK
        varchar name
        varchar slug UK
    }

    post_tag {
        bigint id PK
        bigint post_id FK
        bigint tag_id FK
    }

    post_comments {
        bigint id PK
        bigint post_id FK
        bigint user_id
        varchar user_type
        text content
        bigint parent_id FK
        boolean is_approved
    }

    users ||--o| teachers : "có hồ sơ"
    users ||--o{ model_has_roles : "vai trò"
    roles ||--o{ model_has_roles : ""
    roles ||--o{ role_has_permissions : ""
    permissions ||--o{ role_has_permissions : ""
    teachers ||--o{ courses : "dạy"
    courses }o--o{ categories : ""
    categories_courses }|--|| courses : ""
    categories_courses }|--|| categories : ""
    categories ||--o| categories : "danh mục cha"
    courses }o--o{ students : "đăng ký"
    students_course }|--|| courses : ""
    students_course }|--|| students : ""
    courses ||--|{ sections : "chương"
    sections ||--|{ lessons : "bài học"
    lessons o|--o| media_files : "video"
    students ||--o{ lesson_progress : "tiến độ"
    lessons ||--o{ lesson_progress : ""
    lessons ||--o| quizzes : "bài kiểm tra"
    quizzes ||--|{ quiz_questions : "câu hỏi"
    quizzes ||--o{ quiz_attempts : "lịch sử thi"
    students ||--o{ quiz_attempts : "làm bài"
    lessons ||--o{ quiz_generation_jobs : "AI tạo quiz"
    students ||--o{ orders : "đặt hàng"
    orders ||--|{ order_items : "sản phẩm"
    order_items }|--|| courses : "khoá học"
    orders ||--o{ transactions : "giao dịch"
    users ||--o{ posts : "viết bài"
    post_categories ||--o{ posts : "phân loại"
    posts }o--o{ tags : "nhãn"
    post_tag }|--|| posts : ""
    post_tag }|--|| tags : ""
    posts ||--o{ post_comments : "bình luận"
    post_comments ||--o{ post_comments : "trả lời"
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/erd-full.md
git commit -m "docs: add full combined ERD for entire system"
```

---

## Task 8: Class Diagram — Auth & Users Domain

**Files:**
- Create: `docs/diagrams/class-auth.md`

- [ ] **Bước 1: Tạo file `docs/diagrams/class-auth.md`**

```markdown
# Sơ đồ Lớp — Auth & Users Domain

```mermaid
classDiagram
    direction TB

    class Model {
        <<abstract>>
        +int id
        +timestamp created_at
        +timestamp updated_at
    }

    class User {
        +string name
        +string email
        +string password
        +string avatar
        +int status
        +timestamp deleted_at
        +teacher() HasOne
        +roles() BelongsToMany
        +permissions() BelongsToMany
        +scopeActive(query) Builder
    }

    class Student {
        +string name
        +string email
        +string password
        +string avatar
        +date date_of_birth
        +timestamp email_verified_at
        +timestamp deleted_at
        +enrolledCourses() BelongsToMany
        +orders() HasMany
        +lessonProgresses() HasMany
        +quizAttempts() HasMany
    }

    class Teachers {
        +int user_id
        +string name
        +string slug
        +string description
        +float exp
        +string image
        +int status
        +timestamp deleted_at
        +user() BelongsTo
        +courses() HasMany
        +scopeActive(query) Builder
    }

    class Role {
        <<Spatie>>
        +string name
        +string guard_name
        +permissions() BelongsToMany
    }

    class Permission {
        <<Spatie>>
        +string name
        +string guard_name
    }

    Model <|-- User
    Model <|-- Student
    Model <|-- Teachers

    User "1" --> "0..1" Teachers : user_id
    User "N" --> "N" Role : model_has_roles
    Role "N" --> "N" Permission : role_has_permissions
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/class-auth.md
git commit -m "docs: add class diagram for auth and users domain"
```

---

## Task 9: Class Diagram — Course & Learning Domain

**Files:**
- Create: `docs/diagrams/class-course-learning.md`

- [ ] **Bước 1: Tạo file `docs/diagrams/class-course-learning.md`**

```markdown
# Sơ đồ Lớp — Course & Learning Domain

```mermaid
classDiagram
    direction TB

    class Model {
        <<abstract>>
        +int id
    }

    class Course {
        +int teacher_id
        +string name
        +string slug
        +decimal price
        +decimal sale_price
        +string level
        +int total_lessons
        +int total_students
        +float rating
        +int status
        +timestamp deleted_at
        +teacher() BelongsTo
        +categories() BelongsToMany
        +students() BelongsToMany
        +sections() HasMany
        +lessons() HasManyThrough
        +scopePublished(query) Builder
    }

    class Category {
        +string name
        +string slug
        +int status
        +int _lft
        +int _rgt
        +int parent_id
        +timestamp deleted_at
        +courses() BelongsToMany
        +parent() BelongsTo
        +children() HasMany
        +ancestors() Collection
        +descendants() Collection
        +scopeActive(query) Builder
    }

    class Section {
        +int course_id
        +string title
        +int order
        +int status
        +timestamp deleted_at
        +course() BelongsTo
        +lessons() HasMany
        +scopePublished(query) Builder
        +scopeOrdered(query) Builder
    }

    class Lesson {
        +int course_id
        +int section_id
        +string title
        +string slug
        +string type
        +text content
        +int video_id
        +int document_id
        +int order
        +bool is_preview
        +int duration
        +int status
        +timestamp deleted_at
        +course() BelongsTo
        +section() BelongsTo
        +video() BelongsTo
        +document() BelongsTo
        +progresses() HasMany
        +scopePublished(query) Builder
    }

    class LessonProgress {
        +int student_id
        +int lesson_id
        +int course_id
        +bool is_completed
        +int watched_seconds
        +timestamp completed_at
        +lesson() BelongsTo
        +student() BelongsTo
        +course() BelongsTo
    }

    class MediaFile {
        +string disk
        +string type
        +string original_name
        +string url
        +int size
        +string status
        +int reference_count
        +int duration
        +int uploaded_by
    }

    class Teachers {
        +string name
        +timestamp deleted_at
        +courses() HasMany
    }

    class Student {
        +string name
        +enrolledCourses() BelongsToMany
        +lessonProgresses() HasMany
    }

    Model <|-- Course
    Model <|-- Category
    Model <|-- Section
    Model <|-- Lesson
    Model <|-- LessonProgress
    Model <|-- MediaFile

    Teachers "1" --> "N" Course : teacher_id
    Course "N" --> "N" Category : categories_courses
    Course "N" --> "N" Student : students_course
    Course "1" --> "N" Section : course_id
    Section "1" --> "N" Lesson : section_id
    Course "1" --> "N" Lesson : hasManyThrough
    Lesson "1" --> "0..1" MediaFile : video_id
    Lesson "1" --> "0..1" MediaFile : document_id
    Student "1" --> "N" LessonProgress : student_id
    Lesson "1" --> "N" LessonProgress : lesson_id
    Category "1" --> "0..N" Category : parent_id (self-ref)
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/class-course-learning.md
git commit -m "docs: add class diagram for course and learning domain"
```

---

## Task 10: Class Diagram — Quiz Domain

**Files:**
- Create: `docs/diagrams/class-quiz.md`

- [ ] **Bước 1: Tạo file `docs/diagrams/class-quiz.md`**

```markdown
# Sơ đồ Lớp — Quiz Domain

```mermaid
classDiagram
    direction TB

    class Model {
        <<abstract>>
        +int id
    }

    class Quiz {
        +int lesson_id
        +string title
        +text description
        +int max_attempts
        +int time_limit
        +int status
        +timestamp deleted_at
        +lesson() BelongsTo
        +questions() HasMany
        +attempts() HasMany
        +scopePublished(query) Builder
        +scopeActive(query) Builder
    }

    class QuizQuestion {
        +int quiz_id
        +text question
        +string option_a
        +string option_b
        +string option_c
        +string option_d
        +string correct_option
        +int order
        +quiz() BelongsTo
    }

    class QuizAttempt {
        +int quiz_id
        +int student_id
        +int score
        +int total_questions
        +array answers
        +timestamp completed_at
        +quiz() BelongsTo
        +student() BelongsTo
    }

    class QuizGenerationJob {
        +int lesson_id
        +string status
        +array payload
        +array result
        +text error
    }

    class AIQuizService {
        <<Service>>
        +generateFromPdfText(lessonId, jobId) void
        -callGeminiApi(prompt) array
        -saveQuestions(quizId, questions) void
    }

    class GenerateQuizJob {
        <<Job>>
        +int tries = 3
        +int timeout = 120
        +int quizGenerationJobId
        +int lessonId
        +handle(service) void
        +failed(exception) void
    }

    class Lesson {
        +string title
        +string type
        +quiz() HasOne
    }

    class Student {
        +string name
        +quizAttempts() HasMany
    }

    Model <|-- Quiz
    Model <|-- QuizQuestion
    Model <|-- QuizAttempt
    Model <|-- QuizGenerationJob

    Lesson "1" --> "0..1" Quiz : lesson_id
    Quiz "1" --> "N" QuizQuestion : quiz_id
    Quiz "1" --> "N" QuizAttempt : quiz_id
    Student "1" --> "N" QuizAttempt : student_id
    Lesson "1" --> "N" QuizGenerationJob : lesson_id
    GenerateQuizJob --> AIQuizService : uses
    GenerateQuizJob --> QuizGenerationJob : updates status
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/class-quiz.md
git commit -m "docs: add class diagram for quiz domain"
```

---

## Task 11: Class Diagram — Payment Domain

**Files:**
- Create: `docs/diagrams/class-payment.md`

- [ ] **Bước 1: Tạo file `docs/diagrams/class-payment.md`**

```markdown
# Sơ đồ Lớp — Payment Domain

```mermaid
classDiagram
    direction TB

    class Model {
        <<abstract>>
        +int id
    }

    class Order {
        +string order_code
        +int student_id
        +decimal subtotal
        +decimal discount_amount
        +decimal total_amount
        +string coupon_code
        +string status
        +string payment_method
        +timestamp paid_at
        +timestamp deleted_at
        +student() BelongsTo
        +items() HasMany
        +transactions() HasMany
        +scopePaid(query) Builder
        +scopePending(query) Builder
        +scopeFailed(query) Builder
        +isPaid() bool
        +isPending() bool
        +isFailed() bool
    }

    class OrderItem {
        +int order_id
        +int course_id
        +decimal price
        +decimal sale_price
        +decimal final_price
        +order() BelongsTo
        +course() BelongsTo
    }

    class Transaction {
        +int order_id
        +string gateway
        +string transaction_code
        +string bank_code
        +string card_type
        +decimal amount
        +string status
        +json gateway_response
        +string response_code
        +timestamp paid_at
        +order() BelongsTo
        +isSuccess() bool
        +isPending() bool
    }

    class Coupon {
        +string code
        +string type
        +decimal value
        +decimal min_order_value
        +decimal max_discount
        +int usage_limit
        +int used_count
        +timestamp start_date
        +timestamp end_date
        +int status
        +timestamp deleted_at
        +scopeActive(query) Builder
        +scopeValid(query) Builder
        +isValid() bool
        +calculateDiscount(amount) decimal
    }

    class VnpayService {
        <<Service>>
        +createPaymentUrl(order) string
        +handleIpn(params) array
        +verifyChecksum(params, secret) bool
        +enrollStudent(order) void
    }

    class Student {
        +string name
        +orders() HasMany
    }

    class Course {
        +string name
        +decimal price
    }

    Model <|-- Order
    Model <|-- OrderItem
    Model <|-- Transaction
    Model <|-- Coupon

    Student "1" --> "N" Order : student_id
    Order "1" --> "N" OrderItem : order_id
    OrderItem "N" --> "1" Course : course_id
    Order "1" --> "N" Transaction : order_id
    VnpayService --> Order : cập nhật trạng thái
    VnpayService --> Transaction : tạo giao dịch
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/class-payment.md
git commit -m "docs: add class diagram for payment domain"
```

---

## Task 12: Class Diagram — Content/Posts Domain

**Files:**
- Create: `docs/diagrams/class-content.md`

- [ ] **Bước 1: Tạo file `docs/diagrams/class-content.md`**

```markdown
# Sơ đồ Lớp — Content & Posts Domain

```mermaid
classDiagram
    direction TB

    class Model {
        <<abstract>>
        +int id
    }

    class Post {
        +string title
        +string slug
        +text content
        +string thumbnail
        +int author_id
        +int post_category_id
        +bool is_published
        +timestamp published_at
        +int views
        +timestamp deleted_at
        +author() BelongsTo
        +category() BelongsTo
        +tags() BelongsToMany
        +comments() HasMany
    }

    class PostCategory {
        +string name
        +string slug
        +text description
        +posts() HasMany
    }

    class Tag {
        +string name
        +string slug
        +posts() BelongsToMany
    }

    class PostComment {
        +int post_id
        +int user_id
        +string user_type
        +text content
        +int parent_id
        +bool is_approved
        +post() BelongsTo
        +adminUser() BelongsTo
        +student() BelongsTo
        +parent() BelongsTo
        +replies() HasMany
        +getCommenterAttribute() User or Student
    }

    class User {
        +string name
        +posts() HasMany
        +comments() HasMany
    }

    class Student {
        +string name
        +comments() HasMany
    }

    Model <|-- Post
    Model <|-- PostCategory
    Model <|-- Tag
    Model <|-- PostComment

    User "1" --> "N" Post : author_id
    PostCategory "1" --> "N" Post : post_category_id
    Post "N" --> "N" Tag : post_tag (pivot)
    Post "1" --> "N" PostComment : post_id
    PostComment "0..1" --> "N" PostComment : parent_id (self-ref)
    User "1" --> "N" PostComment : user_id (user_type=User)
    Student "1" --> "N" PostComment : user_id (user_type=Student)
```
```

- [ ] **Bước 2: Commit**

```bash
git add docs/diagrams/class-content.md
git commit -m "docs: add class diagram for content and posts domain"
```

---

## Task 13: Class Diagram — Full Combined

**Files:**
- Create: `docs/diagrams/class-full.md`

- [ ] **Bước 1: Tạo file `docs/diagrams/class-full.md`**

```markdown
# Sơ đồ Lớp — Toàn bộ hệ thống

> Diagram tổng hợp. Khuyến nghị: xem từng domain riêng để dễ đọc hơn.
> Export: dán code vào [mermaid.live](https://mermaid.live) → Download SVG

```mermaid
classDiagram
    direction TB

    %% ─── Auth ───────────────────────────────────────────────
    class User {
        +string name
        +string email
        +int status
        +teacher() HasOne
        +roles() BelongsToMany
    }

    class Student {
        +string name
        +string email
        +timestamp email_verified_at
        +enrolledCourses() BelongsToMany
        +orders() HasMany
        +quizAttempts() HasMany
    }

    class Teachers {
        +int user_id
        +string name
        +float exp
        +courses() HasMany
        +user() BelongsTo
    }

    %% ─── Course & Learning ──────────────────────────────────
    class Course {
        +int teacher_id
        +string slug
        +decimal price
        +int status
        +teacher() BelongsTo
        +categories() BelongsToMany
        +students() BelongsToMany
        +sections() HasMany
        +lessons() HasManyThrough
    }

    class Category {
        +string slug
        +int parent_id
        +courses() BelongsToMany
        +children() HasMany
    }

    class Section {
        +int course_id
        +int order
        +lessons() HasMany
    }

    class Lesson {
        +int section_id
        +string type
        +int video_id
        +int document_id
        +bool is_preview
        +video() BelongsTo
        +document() BelongsTo
        +progresses() HasMany
        +quiz() HasOne
    }

    class LessonProgress {
        +int student_id
        +int lesson_id
        +bool is_completed
        +int watched_seconds
    }

    class MediaFile {
        +string type
        +string url
        +string status
    }

    %% ─── Quiz ───────────────────────────────────────────────
    class Quiz {
        +int lesson_id
        +int max_attempts
        +int time_limit
        +questions() HasMany
        +attempts() HasMany
    }

    class QuizQuestion {
        +int quiz_id
        +text question
        +string correct_option
    }

    class QuizAttempt {
        +int quiz_id
        +int student_id
        +int score
        +array answers
    }

    class QuizGenerationJob {
        +int lesson_id
        +string status
        +array result
    }

    %% ─── Payment ────────────────────────────────────────────
    class Order {
        +int student_id
        +decimal total_amount
        +string status
        +items() HasMany
        +transactions() HasMany
        +isPaid() bool
    }

    class OrderItem {
        +int order_id
        +int course_id
        +decimal final_price
    }

    class Transaction {
        +int order_id
        +string gateway
        +string status
        +json gateway_response
        +isSuccess() bool
    }

    class Coupon {
        +string code
        +string type
        +decimal value
        +isValid() bool
        +calculateDiscount(amount) decimal
    }

    %% ─── Content ────────────────────────────────────────────
    class Post {
        +int author_id
        +string slug
        +bool is_published
        +tags() BelongsToMany
        +comments() HasMany
    }

    class PostCategory {
        +string slug
        +posts() HasMany
    }

    class Tag {
        +string slug
        +posts() BelongsToMany
    }

    class PostComment {
        +int post_id
        +int parent_id
        +string user_type
        +replies() HasMany
    }

    %% ─── Relationships ──────────────────────────────────────
    User "1" --> "0..1" Teachers : has one
    Teachers "1" --> "N" Course : teaches
    Course "N" --> "N" Category : categories_courses
    Course "N" --> "N" Student : students_course
    Course "1" --> "N" Section : has
    Section "1" --> "N" Lesson : contains
    Lesson "1" --> "0..1" MediaFile : video
    Lesson "1" --> "0..1" MediaFile : document
    Lesson "1" --> "0..1" Quiz : has
    Quiz "1" --> "N" QuizQuestion : has
    Quiz "1" --> "N" QuizAttempt : attempts
    Student "1" --> "N" QuizAttempt : submits
    Student "1" --> "N" LessonProgress : tracks
    Lesson "1" --> "N" LessonProgress : tracked
    Lesson "1" --> "N" QuizGenerationJob : AI job
    Student "1" --> "N" Order : places
    Order "1" --> "N" OrderItem : contains
    OrderItem "N" --> "1" Course : for
    Order "1" --> "N" Transaction : paid via
    User "1" --> "N" Post : authors
    PostCategory "1" --> "N" Post : categorizes
    Post "N" --> "N" Tag : tagged
    Post "1" --> "N" PostComment : has
    PostComment "0..1" --> "N" PostComment : replies
```
```

- [ ] **Bước 2: Commit cuối**

```bash
git add docs/diagrams/class-full.md
git commit -m "docs: add full combined class diagram for entire system"
```

---

## Self-Review

### Spec coverage check

| Yêu cầu | Task thực hiện |
|---------|---------------|
| ERD Auth domain | Task 2 |
| ERD Course/Learning domain | Task 3 |
| ERD Quiz domain | Task 4 |
| ERD Payment domain | Task 5 |
| ERD Content domain | Task 6 |
| ERD Full combined | Task 7 |
| Class Diagram Auth domain | Task 8 |
| Class Diagram Course/Learning | Task 9 |
| Class Diagram Quiz | Task 10 |
| Class Diagram Payment | Task 11 |
| Class Diagram Content | Task 12 |
| Class Diagram Full combined | Task 13 |
| Index/README | Task 1 |

Tất cả 21 bảng đều được bao phủ trong các ERD domain. Tất cả 21 Eloquent model đều xuất hiện trong các class diagram domain tương ứng.

### Placeholder scan

Không có placeholder — toàn bộ Mermaid code được viết đầy đủ trong từng task.

### Type consistency

- Tất cả relationship method names (BelongsTo, HasMany, BelongsToMany, HasManyThrough, HasOne) nhất quán giữa domain diagrams và full diagram.
- FK column names (teacher_id, course_id, section_id, v.v.) nhất quán với migrations.
- Enum values (level, status, type) khớp với migrations.

---

## Ghi chú kỹ thuật

- **teachers.user_id**: được thêm qua migration riêng `database/migrations/2026_05_04_081210_add_user_id_to_teachers_table.php` (nullable, onDelete set null)
- **post_comments.user_id**: là polymorphic-style (không dùng morph, dùng user_type string thủ công) — có thể là User hoặc Student
- **lesson_progress**: unique constraint trên (student_id, lesson_id) — upsert khi cập nhật tiến độ
- **categories**: dùng Kalnoy NestedSet — _lft, _rft là internal fields, không nên expose trong API
- **Spatie RBAC**: chỉ áp dụng cho guard `admin` (User model), không áp dụng cho Students
