# E-Learning Marketplace — CLAUDE.md

## Project Overview
Final-year thesis (KLTN) — a full-stack e-learning marketplace with separate student and admin interfaces.
**Deadline: 15/05/2026**

## Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel + Nwidart Modules | PHP ^8.2, Laravel ^12.0 |
| Auth | Laravel Sanctum | ^4.0 |
| ACL | Spatie Permission | ^6.24 |
| Categories | Kalnoy NestedSet | ^6.0 |
| Frontend | Vue 3 + TypeScript | ^3.5.29 |
| State | Pinia | ^3.0.4 |
| HTTP | Axios | ^1.13.6 |
| Styling | Tailwind CSS | ^3.4.19 |
| Build | Vite | ^7.3.1 |
| Database | MySQL | port 3306 |

## Ports (local dev)

| Service | URL |
|---------|-----|
| Backend API | http://localhost:8000 |
| Frontend | http://localhost:5173 |
| MySQL | 127.0.0.1:3306 |
| DB name | `e_learning` |

## Folder Structure

```
e-learning/
├── e-learning-backend/
│   ├── Modules/              ← All feature modules (Nwidart)
│   │   ├── Auth/
│   │   ├── Users/
│   │   ├── Students/
│   │   ├── Teachers/
│   │   ├── Course/
│   │   ├── Lessons/
│   │   ├── Categories/
│   │   └── Upload/
│   ├── app/
│   │   ├── Http/Controllers/ (base Controller only)
│   │   ├── Repositories/     (BaseRepository + RepositoryInterface)
│   │   └── Traits/           (ApiResponse trait)
│   └── database/             (global migrations if any)
│
└── e-learning-frontend/
    └── src/
        ├── api/              ← One file per resource (authApi.js, etc.)
        ├── components/
        │   ├── admin/
        │   ├── client/
        │   ├── common/
        │   └── layout/
        ├── composables/      ← useTheme.ts, useSidebar.ts, etc.
        ├── pages/
        │   ├── admin/
        │   ├── client/
        │   └── auth/
        ├── router/
        └── stores/           ← Pinia stores
```

## Quick Start

```bash
# Terminal 1 — Backend
cd e-learning-backend
php artisan serve         # http://localhost:8000
php artisan queue:work    # background jobs

# Terminal 2 — Frontend
cd e-learning-frontend
npm run dev               # http://localhost:5173

# Reset DB
php artisan migrate:fresh --seed && php artisan storage:link
```

## Test Accounts (after seed)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@elearning.com | password |
| Admin | admin@elearning.com | password |
| Student | student@elearning.com | password |

## Key Conventions

- **Two auth guards**: `admin` (staff) and `api` (students) — never mix them
- **All API routes** prefixed `/api/v1`
- **Response format**: always `{ success, message, data, [pagination] }` via `ApiResponse` trait
- **Repository pattern**: every module has `RepositoryInterface` + `BaseRepository` implementation
- **Soft deletes** on most models — always check for trashed routes
- **Form Requests** handle all validation — never validate in controllers
- **Resources** transform all model output — never return raw models
