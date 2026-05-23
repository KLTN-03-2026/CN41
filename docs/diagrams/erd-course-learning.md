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
