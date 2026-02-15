# Phase 6: Detailed Developer Tasks

## 1. Social Login
- **Description**: Integrate Socialite.
- **Tasks**:
    1. Install `laravel/socialite`.
    2. Configure Google/Github credentials in `.env` and `services.php`.
    3. Create `SocialAuthController` (redirect, callback).
    4. Update Login Page to include "Login with Google" button.

## 2. User Avatars
- **Description**: Profile pictures.
- **Tasks**:
    1. Update `User` model with `InteractsWithMedia`.
    2. Update `Profile/SimpleUpdate.vue` to include file upload.
    3. Display avatar in Topbar and User List.

## 3. Audit Logs
- **Note**: Moved to Phase 1 as requested.

## 4. Advanced Product Logic
- **Tax Logic**: Implement `TaxClass` model and link to Products.
- **Bundles**: Implement `Bundle` model and logic to deduct stock from children when bundle is sold.
