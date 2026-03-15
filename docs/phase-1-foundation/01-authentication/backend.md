# Authentication — Backend

## Implementation Steps

### 1. Custom Authentication Logic
Since we are not using Breeze or Jetstream, implement authentication manually.

**Create `AuthController`:**
```
app/Http/Controllers/Auth/AuthController.php
```

- `showLogin()` — return Inertia login page
- `login()` — validate credentials, check user status, authenticate, redirect by role
- `logout()` — invalidate session, redirect to login

**Login logic (check status before authenticating):**
```php
$user = User::where('email', $request->login)
    ->orWhere('username', $request->login)
    ->first();

if (!$user || !Hash::check($request->password, $user->password)) {
    throw ValidationException::withMessages([
        'login' => [__('auth.failed')],
    ]);
}

if ($user->status !== 'active') {
    throw ValidationException::withMessages([
        'login' => [__('auth.inactive')],
    ]);
}

Auth::login($user, $request->boolean('remember'));
```

---

### 2. Role-Based Redirect After Login
After successful login, redirect based on the user's role:

```php
private function redirectAfterLogin(User $user): string
{
    return $user->hasRole('admin') ? route('dashboard') : route('pos.index');
}
```

---

### 3. Password Reset
Use Laravel's built-in password broker:

```
app/Http/Controllers/Auth/PasswordResetController.php
```

- `showForgotForm()` — Inertia forgot password page
- `sendResetLink()` — call `Password::sendResetLink()`
- `showResetForm()` — Inertia reset password page
- `resetPassword()` — call `Password::reset()`

---

### 4. Rate Limiting
Define in `app/Http/Kernel.php` or `bootstrap/app.php` (Laravel 12):

```php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by(
        Str::transliterate(Str::lower($request->input('login')).'|'.$request->ip())
    );
});
```

---

### 5. Routes
```php
// routes/auth.php (included from web.php)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->post('/logout', [AuthController::class, 'logout'])->name('logout');
```

---

### 6. User Model Customization
Ensure `User` model implements `Authenticatable` and uses the correct fields:

```php
protected $fillable = ['first_name', 'last_name', 'email', 'username', 'password', 'status', ...];

public function getAuthIdentifierName(): string
{
    return 'email'; // fallback, actual lookup is manual
}
```

---

### 7. Inertia Shared Data
Share authenticated user data globally for all pages via `HandleInertiaRequests` middleware:

```php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->full_name,
                'email' => $request->user()->email,
                'roles' => $request->user()->getRoleNames(),
                'permissions' => $request->user()->getAllPermissions()->pluck('name'),
            ] : null,
        ],
        'flash' => [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
        ],
    ]);
}
```

---

## Good Practices
- Never reveal whether the email or password was wrong — use a generic error
- Always hash passwords with bcrypt (Laravel default)
- Use `Auth::logoutOtherDevices($password)` if you want single-session enforcement
- Log login events via Spatie Activity Log
- Store `remember_me` token securely (Laravel handles this automatically)
