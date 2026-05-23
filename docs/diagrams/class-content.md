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
