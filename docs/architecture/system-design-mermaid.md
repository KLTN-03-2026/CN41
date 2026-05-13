# Thiết kế Hệ thống E-Learning Marketplace (Mermaid Diagrams)

Tài liệu này chứa các sơ đồ thiết kế hệ thống được vẽ bằng Mermaid, phục vụ cho báo cáo đồ án.

---

## 1. Sơ đồ Use Case tổng thể

Sơ đồ này mô tả các tác nhân và các chức năng chính, được trình bày dưới dạng Flowchart để đảm bảo hiển thị tốt nhất trên mọi trình xem Markdown.

```mermaid
graph LR
    %% Actors
    subgraph Actors
        Student[("Học viên (Student)")]
        Teacher[("Giảng viên (Teacher)")]
        Admin[("Quản trị viên (Admin)")]
    end

    %% Use Cases
    subgraph UC_Student ["Phân hệ Học viên"]
        UC1(["Đăng ký/Đăng nhập"])
        UC2(["Tìm kiếm & Xem khóa học"])
        UC3(["Thanh toán khóa học (VNPay)"])
        UC4(["Học bài & Theo dõi tiến độ"])
        UC5(["Làm bài Quiz đánh giá"])
    end

    subgraph UC_Teacher ["Phân hệ Giảng viên"]
        UC6(["Quản lý Khóa học cá nhân"])
        UC7(["Tạo bài giảng & Tài liệu"])
        UC8(["Sinh Quiz tự động (AI Gemini)"])
        UC9(["Thống kê doanh thu cá nhân"])
    end

    subgraph UC_Admin ["Phân hệ Quản trị"]
        UC10(["Quản lý User & Phân quyền"])
        UC11(["Kiểm duyệt toàn bộ nội dung"])
        UC12(["Quản lý Danh mục & Bài viết"])
        UC13(["Quản lý Coupon & Order"])
    end

    %% Relationships
    Student --- UC1
    Student --- UC2
    Student --- UC3
    Student --- UC4
    Student --- UC5

    Teacher --- UC1
    Teacher --- UC6
    Teacher --- UC7
    Teacher --- UC8
    Teacher --- UC9

    Admin --- UC1
    Admin --- UC10
    Admin --- UC11
    Admin --- UC12
    Admin --- UC13

    %% Cross-actor rights
    UC11 -.-> UC6 : "Admin can edit any course"
```

---

## 2. Sơ đồ Thực thể Liên kết (ERD Toàn diện)

Sơ đồ ERD bao quát toàn bộ 27 bảng, tập trung vào các mối quan hệ logic quan trọng nhất giữa các phân hệ.

```mermaid
erDiagram
    %% Identity Domain
    USERS ||--|| TEACHERS : "mở rộng Profile"
    USERS {
        bigint id PK
        string name
        string email
        string password
    }
    TEACHERS {
        bigint id PK
        text bio
        float exp
        tinyint status
    }
    STUDENTS {
        bigint id PK
        string name
        string email
        timestamp email_verified_at
    }

    %% Course Domain
    TEACHERS ||--o{ COURSES : "giảng dạy"
    COURSES ||--o{ SECTIONS : "cấu trúc"
    SECTIONS ||--o{ LESSONS : "chi tiết"
    CATEGORIES ||--o{ CATEGORIES : "phân cấp NestedSet"
    CATEGORIES }o--o{ COURSES : "thuộc danh mục"

    %% Learning & Quiz Domain
    LESSONS ||--o| QUIZZES : "bài tập đi kèm"
    QUIZZES ||--o{ QUIZ_QUESTIONS : "danh sách câu hỏi"
    STUDENTS ||--o{ QUIZ_ATTEMPTS : "thực hiện làm bài"
    QUIZZES ||--o{ QUIZ_ATTEMPTS : "lưu kết quả"
    STUDENTS ||--o{ LESSON_PROGRESS : "theo dõi tiến trình"
    LESSONS ||--o{ LESSON_PROGRESS : "đánh giá hoàn thành"

    %% Payment Domain
    STUDENTS ||--o{ ORDERS : "đặt mua"
    ORDERS ||--o{ ORDER_ITEMS : "danh sách khóa học"
    ORDER_ITEMS }o--|| COURSES : "tham chiếu"
    ORDERS ||--o| TRANSACTIONS : "giao dịch VNPay"
    COUPONS ||--o{ ORDERS : "áp dụng giảm giá"

    %% Blog & Social
    USERS ||--o{ POSTS : "tác giả bài viết"
    POSTS ||--o{ POST_COMMENTS : "bình luận"
    STUDENTS ||--o{ POST_COMMENTS : "viết bình luận"
    POSTS }o--o{ TAGS : "gắn nhãn N:M"

    %% AI Integration
    LESSONS ||--o{ QUIZ_GENERATION_JOBS : "trạng thái AI generation"
```

---

## 3. Kiến trúc Modular Monolith (Class Diagram)

Mô hình hóa cách các Module tương tác thông qua Repository Pattern.

```mermaid
classDiagram
    class BaseRepository {
        <<Interface>>
        +all()
        +find(id)
        +create(data)
        +update(id, data)
        +delete(id)
    }
    
    class CourseRepository {
        +getPublishedCourses()
        +getTeacherCourses(teacherId)
    }
    
    class CourseController {
        -CourseRepository repository
        +index()
        +show()
        +store(CourseRequest)
    }
    
    class Course {
        <<Model>>
        +teacher()
        +sections()
        +scopePublished()
    }
    
    class AIQuizService {
        +generateQuestions(context)
        -callGeminiAPI(prompt)
    }

    BaseRepository <|-- CourseRepository
    CourseController --> CourseRepository : injects
    CourseRepository --> Course : uses
    CourseController ..> AIQuizService : uses for AI features
```

---

## 4. Luồng AI Quiz Generation (Sequence Diagram)

Sơ đồ mô tả quy trình xử lý bất đồng bộ khi sinh câu hỏi bằng Gemini AI.

```mermaid
sequenceDiagram
    participant T as Teacher/Admin
    participant B as Backend (Laravel)
    participant Q as Queue (Worker)
    participant AI as Google Gemini API
    participant DB as Database (MySQL)

    T->>B: Gửi yêu cầu sinh Quiz (lesson_id, count, source)
    B->>DB: Tạo Job (status: pending)
    B->>Q: Dispatch GenerateQuizJob
    B-->>T: Trả về Job ID (202 Accepted)
    
    Note over Q, AI: Xử lý chạy ngầm
    Q->>DB: Cập nhật Job (status: processing)
    Q->>B: Trích xuất Text từ PDF (nếu có)
    Q->>AI: Gửi Prompt + Context (Gemini 2.0 Flash)
    AI-->>Q: Trả về JSON questions
    Q->>DB: Lưu Quiz & Questions vào database
    Q->>DB: Cập nhật Job (status: done)
    
    loop Polling
        T->>B: Kiểm tra trạng thái Job
        B->>DB: Truy vấn Job status
        DB-->>B: Trả về status
        B-->>T: Hiển thị kết quả/thông báo hoàn thành
    end
```
