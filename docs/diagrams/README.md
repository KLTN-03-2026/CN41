# Tài liệu Sơ đồ Hệ thống E-Learning

## Biểu đồ Quan hệ (ERD)

| Sơ đồ | Mô tả | Link |
|-------|-------|------|
| Auth & Users | users, students, teachers, roles, permissions | [erd-auth.md](./erd-auth.md) |
| Course & Learning | courses, categories, sections, lessons, progress | [erd-course-learning.md](./erd-course-learning.md) |
| Quiz | quizzes, questions, attempts, AI jobs | [erd-quiz.md](./erd-quiz.md) |
| Payment | orders, items, transactions, coupons | [erd-payment.md](./erd-payment.md) |
| Content/Posts | posts, categories, tags, comments | [erd-content.md](./erd-content.md) |
| **Full ERD** | Toàn bộ hệ thống | [erd-full.md](./erd-full.md) |

## Sơ đồ Lớp (Class Diagram)

| Sơ đồ | Mô tả | Link |
|-------|-------|------|
| Auth & Users | User, Student, Teachers | [class-auth.md](./class-auth.md) |
| Course & Learning | Course, Category, Section, Lesson, MediaFile | [class-course-learning.md](./class-course-learning.md) |
| Quiz | Quiz, QuizQuestion, QuizAttempt | [class-quiz.md](./class-quiz.md) |
| Payment | Order, OrderItem, Transaction, Coupon | [class-payment.md](./class-payment.md) |
| Content/Posts | Post, PostCategory, Tag, PostComment | [class-content.md](./class-content.md) |
| **Full Class Diagram** | Toàn bộ hệ thống | [class-full.md](./class-full.md) |

## Hướng dẫn render

- **GitHub/GitLab**: Tự động render Mermaid trong Markdown
- **VS Code**: Cài extension "Markdown Preview Mermaid Support"
- **Export PNG**: Dán code vào [mermaid.live](https://mermaid.live) → Download SVG/PNG
- **PlantUML alternative**: Dùng [plantuml.com](https://plantuml.com) nếu cần UML chuẩn hơn
