# Git Workflow

> Summarized from `GIT_WORKFLOW.md` at repo root. See that file for full detail.

## Branch Strategy
Single main branch: `main` — always deployable.

| Type | Pattern | Example |
|------|---------|---------|
| New feature | `feature/<name>` | `feature/payment-vnpay` |
| Bug fix | `fix/<desc>` | `fix/thumbnail-not-loading` |
| UI change | `ui/<screen>` | `ui/course-detail-page` |
| Urgent fix | `hotfix/<desc>` | `hotfix/login-crash` |
| Experiment | `experiment/<name>` | `experiment/ai-quiz` |

- Use `-` not `_` or spaces
- **Delete branches after merging** — no stale branches

## Commit Message Format

```
<type>(<scope>): <short description>
```

**Types**: `feat` · `fix` · `ui` · `refactor` · `chore` · `docs` · `test` · `perf`

**Scopes**: `auth` · `course` · `lesson` · `section` · `category` · `teacher` · `student` · `payment` · `upload` · `dashboard` · `frontend` · `backend` · `api`

```bash
feat(payment): integrate VNPay payment gateway
fix(course): fix category not saving on create
ui(course-detail): improve sticky sidebar layout
refactor(lesson): extract LessonController into service layer
chore(deps): upgrade laravel/sanctum to v4.3
```

Commit small and often — each commit = one meaningful unit of change.

## Daily Workflow

```bash
# Start of day
git checkout main
git pull origin main

# End of day (if branch has unfinished work)
git add .
git commit -m "wip: <description>"
git push origin <branch>
```

## Feature Flow

```bash
git checkout main
git checkout -b feature/my-feature

# ... code, commit frequently ...

git checkout main
git merge feature/my-feature
git push origin main
git branch -d feature/my-feature
```

## Remotes

| Remote | URL | Purpose |
|--------|-----|---------|
| `origin` | github.com/KLTN-03-2026/CN41.git | Main group repo |
| `backend-origin` | github.com/ahryxx0602/e-learning-backend | BE solo repo |
| `frontend-origin` | github.com/ahryxx0602/e-learning-frontend | FE solo repo |

## Rules
- `main` must always run — never commit broken code
- Prefer `git add <specific-files>` over `git add .`
- Fix conflicts before pushing; never force-push to `main`
- **Deadline: 15/05/2026**
