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
