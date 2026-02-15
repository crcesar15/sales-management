# Task: Setup Authentication

## Goal
Implement secure Login and Logout functionality using Laravel Sanctum/Fortify and a Vue 3 Guest Layout.

## Technical Implementation

### 1. Backend Configuration
- [ ] **Verify User Model**: Ensure `app/Models/User.php` uses `Laravel\Sanctum\HasApiTokens`.
- [ ] **CORS & Sanctum**:
    -   Check `config/cors.php`: `supports_credentials` must be `true`.
    -   Check `config/sanctum.php`: `stateful` domain should include `localhost` or your local domain.
- [ ] **Middleware**:
    -   Ensure `EnsureFrontendRequestsAreStateful` class is uncommented in `bootstrap/app.php` (Laravel 11) or `app/Http/Kernel.php` (Laravel 10).
- [ ] **Routes**:
    -   If using **Fortify**: Verify `config/fortify.php` has `features.login` enabled.
    -   If manual: Create `AuthController` with `login(Request $request)` and `logout(Request $request)`.
        -   `login`: `Auth::attempt($credentials)`, then `$request->session()->regenerate()`.
        -   `logout`: `Auth::logout()`, `$request->session()->invalidate()`.

### 2. Frontend Implementation (Vue 3 + Inertia)
- [ ] **Guest Layout**:
    -   Create `resources/js/Layouts/GuestLayout.vue`.
    -   Use a centered Flexbox/Grid layout with a slot for the form.
- [ ] **Login Page**:
    -   Create `resources/js/Pages/Auth/Login.vue`.
    -   Use `useForm` from `@inertiajs/vue3` for email/password.
    -   Submit to `/login`. Handle validation errors (`form.errors.email`).
- [ ] **Main Layout (Authenticated)**:
    -   Create `resources/js/Layouts/MainLayout.vue`.
    -   **Sidebar**: Links to Dashboard, Users, Roles, Settings.
    -   **Topbar**: User dropdown with "Logout".
    -   **Logout Action**: `router.post(route('logout'))`.

### 3. Verification Steps
-   Run `php artisan serve` and `npm run dev`.
-   Navigate to `/`. Should redirect to `/login`.
-   Login with seeded credentials (from `AdminUserSeeder`).
-   Verify session cookie is set.
