# Authentication — Overview

## What
Implement secure user authentication including login, logout, password reset, and session management.

## Why
Every other module in the system depends on knowing who the user is and what they are allowed to do. Authentication is the entry point to the entire application.

## Requirements
- Login with email or username + password
- Logout and full session invalidation
- Password reset via email token
- Role-based redirect after login (Admin → Dashboard, Sales Rep → POS)
- Session expiry after configurable period of inactivity
- Clear error messages for invalid credentials (without revealing which field is wrong)
- Brute-force protection (rate limiting on login attempts)

## Acceptance Criteria
- [ ] User can log in with a valid email + password
- [ ] User can log in with a valid username + password
- [ ] Invalid credentials show a generic error message (do not specify email vs password)
- [ ] After 5 failed attempts, login is rate-limited for 60 seconds
- [ ] Admin is redirected to the dashboard after login
- [ ] Sales Rep is redirected to the POS after login
- [ ] User can request a password reset email
- [ ] Password reset link expires after 60 minutes
- [ ] User can set a new password via the reset link
- [ ] Logout invalidates the session and redirects to login
- [ ] Inactive user receives a clear message and cannot log in

## Dependencies
- `users` table must exist with `status`, `email`, `username`, and `password` fields
- Mail configuration must be set up for password reset emails

## Notes
- Use Laravel's built-in `Auth` facade and session guard (not token-based for web)
- Do NOT use Laravel Breeze or Jetstream — implement manually to keep full control
- Password reset uses Laravel's built-in `Password::sendResetLink()` flow
