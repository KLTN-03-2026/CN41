# Database Conventions

## Connection
- Engine: MySQL (127.0.0.1:3306)
- Database: `e_learning`
- Charset: utf8mb4 (Laravel default)

## Table Naming
- Models are **singular** PascalCase; tables are **plural** snake_case
- Pivot tables: **alphabetical** snake_case of both models: `categories_courses`, `students_course`

## Migration Naming
Pattern: `YYYY_MM_DD_HHMMSS_<action>_<table>_table.php`

```
2026_04_07_063809_create_courses_table.php
2026_04_07_063810_create_categories_courses_table.php
2026_04_07_063811_create_students_course_table.php
2026_04_07_075228_create_media_files_table.php
```

## Column Conventions

| Pattern | Example |
|---------|---------|
| Foreign keys | `teacher_id`, `course_id` (unsignedBigInteger) |
| Status flags | `tinyInteger('status')->default(0)` — 0=draft/inactive, 1=published/active |
| Timestamps | `$table->timestamps()` on all tables |
| Soft deletes | `$table->softDeletes()` — column `deleted_at` |
| Slugs | `string('slug', 255)->unique()` |
| Prices | `decimal('price', 10, 2)->default(0)` |
| Ratings | `float('rating')->default(0)` |
| Enum columns | `enum('level', ['beginner', 'intermediate', 'advanced'])` |
| Email verification | `email_verified_at` timestamp nullable |

## Key Tables

```
users                   Laravel default users table (admin staff)
students                email, password, email_verified_at, softDeletes
teachers                name, email, bio, avatar
courses                 teacher_id, slug, price, sale_price, level, status, softDeletes
categories              parent_id (NestedSet: _lft, _rgt, depth)
sections                course_id, title, order
lessons                 section_id, slug, is_preview, video_url, softDeletes
media_files             type (video/document), url, size
students_course         course_id, student_id, enrolled_at (pivot with timestamps)
categories_courses      course_id, category_id (simple pivot)
student_email_verifications  student_id, token, expires_at
```

## Model Relationships

```php
// Course
belongsTo(Teachers::class, 'teacher_id')
belongsToMany(Category::class, 'categories_courses', 'course_id', 'category_id')
belongsToMany(Student::class, 'students_course', 'course_id', 'student_id')
    ->withPivot('enrolled_at')->withTimestamps()
hasMany(Section::class)
hasManyThrough(Lesson::class, Section::class)

// Section
belongsTo(Course::class)
hasMany(Lesson::class)

// Lesson
belongsTo(Section::class)

// Student
belongsToMany(Course::class, 'students_course')->withPivot('enrolled_at')

// Category (NestedSet)
parent(), children(), ancestors(), descendants()
```

## Model Scopes

```php
// Course
scopePublished($query)   → where('status', 1)
scopeActive($query)      → where('status', 1)   // alias
```

## Soft Deletes
Models using `SoftDeletes`: `Course`, `Lesson`, `Student`

Always provide trashed/restore/force-delete routes for soft-deleted resources:
```
GET    /admin/{resource}/trashed
PATCH  /admin/{resource}/{id}/restore
DELETE /admin/{resource}/{id}/force-delete
DELETE /admin/{resource}/bulk-delete
```

## Migrations — Do NOT
- Never use `migrate:fresh` in production or staging
- Never add columns without `->nullable()` or `->default()` to existing tables
- Foreign keys always reference the correct table with `onDelete('cascade')` or `onDelete('set null')`
