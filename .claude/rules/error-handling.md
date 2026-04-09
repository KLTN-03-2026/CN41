# Error Handling

## Backend

### Standard Error Response Shape
All errors follow the same envelope as successes:

```json
{
  "success": false,
  "message": "Human-readable message (Vietnamese or English)",
  "data": null,
  "errors": { }
}
```

### Validation Errors (422)
Form Requests override `failedValidation()` — never rely on Laravel's default redirect:

```php
protected function failedValidation(Validator $validator): void
{
    throw new HttpResponseException(response()->json([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ.',
        'errors'  => $validator->errors(),
    ], 422));
}
```

The `errors` field contains field-keyed arrays of messages matching Laravel's standard format.

### Auth / Authorization Errors (401, 403)
Custom middleware returns structured JSON — never HTML:

```php
// EnsureEmailVerified middleware
return response()->json([
    'success' => false,
    'message' => 'Tài khoản chưa được kích hoạt.',
    'data'    => null,
    'errors'  => [
        'email_not_verified' => true,
        'email'              => $student?->email,
    ],
], 403);
```

### Not Found / Server Errors
Use `ApiResponse` trait methods from controllers:
```php
return $this->error('Không tìm thấy khóa học.', 404);
return $this->error('Đã có lỗi xảy ra.', 500);
```

### Transaction Failures
Wrap multi-step writes in `DB::transaction()` — any exception auto-rolls back:
```php
$result = DB::transaction(function () use ($request) {
    $course = $this->repository->create($request->validated());
    $this->repository->syncCategories($course->id, $request->category_ids);
    return $course;
});
```

---

## Frontend

### API Call Pattern
Stores catch errors and return structured results — never throw to components:

```js
async login(email, password) {
  this.loading = true
  try {
    const res = await authApi.studentLogin(email, password)
    this.token = res.data.data.token
    // ...
    return { success: true }
  } catch (err) {
    return {
      success: false,
      message: err.response?.data?.message || 'Đã có lỗi xảy ra.',
      errors: err.response?.data?.errors,
    }
  } finally {
    this.loading = false
  }
}
```

### Component Error Display
Components check the returned `{ success, message, errors }` object:

```js
const result = await store.login(email.value, password.value)
if (!result.success) {
  toast.error(result.message)
  if (result.errors?.email_not_verified) {
    // show resend verification UI
  }
}
```

### Axios Interceptors
Request interceptor adds `Authorization: Bearer <token>` from localStorage.
Response interceptor handles 401 globally (clear token + redirect to login).

### Toast Notifications
Use `vue-toastification` for user-visible feedback:
- Success: `useToast().success('...')`
- Error: `useToast().error('...')`
- Position: top-right, timeout: 3000ms

### Loading State
Every async operation sets a `loading` ref — used to disable buttons and show spinners. Always reset in `finally`.
