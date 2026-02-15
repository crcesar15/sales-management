# Phase 6: Nice to Have Features

**Goal**: Enhance the user experience and system aesthetics with non-critical but valuable features.

## 1. Authentication Enhancements
- [ ] **Social Login**: Allow users to log in with Google, GitHub, or Microsoft accounts (Laravel Socialite).
- [ ] **Two-Factor Authentication (2FA)**: Add an extra layer of security using Authenticator apps (Google/Microsoft Authenticator).

## 2. User Profile Customization
- [ ] **Profile Picture**: Allow users to upload avatars. Use `spatie/laravel-medialibrary` attached to the User model.
- [ ] **Dark Mode Sync**: Persist dark/light mode preference in the database (currently local storage).

## 3. Advanced UI/UX
- [ ] **Global Search**: Search bar in the header to find Products, Orders, or Customers instantly (Command Palette style).
- [ ] **Keyboard Shortcuts**: Hotkeys for POS actions (e.g., F2 for Search, F10 for Pay).

## 4. Notifications
- [ ] **In-App Notifications**: Real-time alerts for low stock or new orders (Pusher/Reverb).

## 5. Advanced Product Logic
- [ ] **Tax Classes**: Global tax rules (e.g., VAT 10%, Zero Rated) linked to products.
- [ ] **Product Bundles**: Ability to group items (Item A + Item B = Bundle C) with auto-stock deduction.
