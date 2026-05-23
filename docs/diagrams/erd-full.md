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
