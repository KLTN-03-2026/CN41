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
