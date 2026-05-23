# Sơ đồ Lớp — Course & Learning Domain

```mermaid
classDiagram
    direction TB

    class Model {
        <<abstract>>
        +int id
    }

    class Course {
        +int teacher_id
        +string name
        +string slug
        +decimal price
        +decimal sale_price
        +string level
        +int total_lessons
        +int total_students
        +float rating
        +int status
        +timestamp deleted_at
        +teacher() BelongsTo
        +categories() BelongsToMany
        +students() BelongsToMany
        +sections() HasMany
        +lessons() HasManyThrough
        +scopePublished(query) Builder
    }

    class Category {
        +string name
        +string slug
        +int status
        +int _lft
        +int _rgt
        +int parent_id
        +timestamp deleted_at
        +courses() BelongsToMany
        +parent() BelongsTo
        +children() HasMany
        +ancestors() Collection
        +descendants() Collection
        +scopeActive(query) Builder
    }

    class Section {
        +int course_id
        +string title
        +int order
        +int status
        +timestamp deleted_at
        +course() BelongsTo
        +lessons() HasMany
        +scopePublished(query) Builder
        +scopeOrdered(query) Builder
    }

    class Lesson {
        +int course_id
        +int section_id
        +string title
        +string slug
        +string type
        +text content
        +int video_id
        +int document_id
        +int order
        +bool is_preview
        +int duration
        +int status
        +timestamp deleted_at
        +course() BelongsTo
        +section() BelongsTo
        +video() BelongsTo
        +document() BelongsTo
        +progresses() HasMany
        +scopePublished(query) Builder
    }

    class LessonProgress {
        +int student_id
        +int lesson_id
        +int course_id
        +bool is_completed
        +int watched_seconds
        +timestamp completed_at
        +lesson() BelongsTo
        +student() BelongsTo
        +course() BelongsTo
    }

    class MediaFile {
        +string disk
        +string type
        +string original_name
        +string url
        +int size
        +string status
        +int reference_count
        +int duration
        +int uploaded_by
    }

    class Teachers {
        +string name
        +timestamp deleted_at
        +courses() HasMany
    }

    class Student {
        +string name
        +enrolledCourses() BelongsToMany
        +lessonProgresses() HasMany
    }

    Model <|-- Course
    Model <|-- Category
    Model <|-- Section
    Model <|-- Lesson
    Model <|-- LessonProgress
    Model <|-- MediaFile

    Teachers "1" --> "N" Course : teacher_id
    Course "N" --> "N" Category : categories_courses
    Course "N" --> "N" Student : students_course
    Course "1" --> "N" Section : course_id
    Section "1" --> "N" Lesson : section_id
    Course "1" --> "N" Lesson : hasManyThrough
    Lesson "1" --> "0..1" MediaFile : video_id
    Lesson "1" --> "0..1" MediaFile : document_id
    Student "1" --> "N" LessonProgress : student_id
    Lesson "1" --> "N" LessonProgress : lesson_id
    Category "1" --> "0..N" Category : parent_id (self-ref)
