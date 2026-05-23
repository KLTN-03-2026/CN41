# Sơ đồ Lớp — Quiz Domain

```mermaid
classDiagram
    direction TB

    class Model {
        <<abstract>>
        +int id
    }

    class Quiz {
        +int lesson_id
        +string title
        +text description
        +int max_attempts
        +int time_limit
        +int status
        +timestamp deleted_at
        +lesson() BelongsTo
        +questions() HasMany
        +attempts() HasMany
        +scopePublished(query) Builder
        +scopeActive(query) Builder
    }

    class QuizQuestion {
        +int quiz_id
        +text question
        +string option_a
        +string option_b
        +string option_c
        +string option_d
        +string correct_option
        +int order
        +quiz() BelongsTo
    }

    class QuizAttempt {
        +int quiz_id
        +int student_id
        +int score
        +int total_questions
        +array answers
        +timestamp completed_at
        +quiz() BelongsTo
        +student() BelongsTo
    }

    class QuizGenerationJob {
        +int lesson_id
        +string status
        +array payload
        +array result
        +text error
    }

    class AIQuizService {
        <<Service>>
        +generateFromPdfText(lessonId, jobId) void
        -callGeminiApi(prompt) array
        -saveQuestions(quizId, questions) void
    }

    class GenerateQuizJob {
        <<Job>>
        +int tries = 3
        +int timeout = 120
        +int quizGenerationJobId
        +int lessonId
        +handle(service) void
        +failed(exception) void
    }

    class Lesson {
        +string title
        +string type
        +quiz() HasOne
    }

    class Student {
        +string name
        +quizAttempts() HasMany
    }

    Model <|-- Quiz
    Model <|-- QuizQuestion
    Model <|-- QuizAttempt
    Model <|-- QuizGenerationJob

    Lesson "1" --> "0..1" Quiz : lesson_id
    Quiz "1" --> "N" QuizQuestion : quiz_id
    Quiz "1" --> "N" QuizAttempt : quiz_id
    Student "1" --> "N" QuizAttempt : student_id
    Lesson "1" --> "N" QuizGenerationJob : lesson_id
    GenerateQuizJob --> AIQuizService : uses
    GenerateQuizJob --> QuizGenerationJob : updates status
