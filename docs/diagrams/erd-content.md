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
