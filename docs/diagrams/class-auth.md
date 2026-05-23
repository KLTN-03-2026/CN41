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
