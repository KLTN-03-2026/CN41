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
