# Code Review Skill

Review code changes in this e-learning project against the established conventions.

## Review Checklist

### Backend (Laravel)

**Architecture**
- [ ] Controller injects repository interface (not concrete class, not Eloquent model)
- [ ] No business logic in controllers — only orchestration
- [ ] No direct Eloquent queries in controllers — only repository calls
- [ ] Complex writes wrapped in `DB::transaction()`

**Response Format**
- [ ] All responses use `ApiResponse` trait methods (`success()`, `error()`, `paginated()`)
- [ ] Never returning raw model or collection — always through a Resource
- [ ] Error messages match the standard envelope `{ success, message, data, errors }`

**Validation**
- [ ] Validation in Form Request, not in controller
- [ ] `failedValidation()` overridden to return JSON (not redirect)
- [ ] Vietnamese error messages in `messages()` array

**Database**
- [ ] New migrations use `softDeletes()` if the model should support soft delete
- [ ] Foreign keys have explicit `onDelete('cascade')` or `onDelete('set null')`
- [ ] No raw `DB::statement()` without necessity
- [ ] `per_page` clamped to [1, 100] in repositories

**Security**
- [ ] Admin routes protected by `auth:admin` middleware
- [ ] Student routes protected by `auth:api` middleware
- [ ] Sensitive routes also have `email.verified`
- [ ] Login/register routes have throttle middleware
- [ ] No user-controlled data in raw SQL

**Module Structure**
- [ ] New features in correct module, not in global `app/`
- [ ] New module registered and enabled

---

### Frontend (Vue 3)

**Component**
- [ ] Using `<script setup lang="ts">` (not Options API)
- [ ] No direct `localStorage` access in components — must go through store
- [ ] Loading state reset in `finally` block
- [ ] Async errors surfaced via `vue-toastification`, not console.log

**Stores**
- [ ] Actions return `{ success, message?, errors? }` — not throwing
- [ ] Token changes (set/clear) also update `localStorage`
- [ ] No raw axios calls in components — must go through `src/api/`

**API Layer**
- [ ] New endpoints added to correct file in `src/api/`
- [ ] Using `http` from `@/plugins/axios` (not bare `axios`)
- [ ] PATCH for partial updates (not PUT)

**Linting**
- [ ] ESLint config uses `eslint-plugin-oxlint` + `.oxlintrc.json` — do not duplicate oxlint rules in eslint config
- [ ] No `@typescript-eslint/no-explicit-any` errors (warn is allowed, but prefer typed alternatives)

**Router**
- [ ] New protected page has correct `meta: { requiresAuth, guard }`
- [ ] Page components are lazy-loaded (`() => import(...)`)

**Naming**
- [ ] Components: `PascalCase.vue`
- [ ] Pages: `PascalCasePage.vue`
- [ ] Composables: `useFeatureName.ts`
- [ ] API files: `resourceNameApi.js`

---

## Common Issues to Flag

1. **Guard mismatch** — admin token used on student route or vice versa
2. **Missing `email.verified`** on routes that need it
3. **Raw model returned** instead of Resource in API response
4. **Validation in controller** instead of Form Request
5. **`migrate:fresh`** suggested outside dev context
6. **`per_page` not clamped** in new repository methods
7. **Store action throws** instead of returning `{ success: false, ... }`
8. **Direct `axios` import** instead of `@/plugins/axios`
9. **No `DB::transaction()`** on multi-table writes
10. **Missing soft delete** on a model that should support trashing
11. **`as` cast inside `<template>`** — Volar parse error, extract to a function in `<script setup>`
12. **`putJson` in feature tests** — convention is PATCH; use `patchJson` for update tests
13. **`--no-verify` on commit** — never skip husky hooks; fix the lint/format issue instead

## How to Give Feedback

- Reference the specific rule violated with a file path
- Suggest the correct pattern based on existing code (e.g., "see CourseController@store for the transaction pattern")
- Flag security issues as `[SECURITY]`, style issues as `[STYLE]`, bugs as `[BUG]`
