# Task: Global Settings Module

## Goal
Create a system to manage global application settings (Name, Currency, Logo).

## Technical Implementation

### 1. Database Schema
- [ ] **Migration**: `php artisan make:migration create_settings_table`
    ```php
    Schema::create('settings', function (Blueprint $table) {
        $table->string('key')->primary();
        $table->text('value')->nullable();
        $table->string('type')->default('string'); // 'string', 'boolean', 'int', 'image'
        $table->string('group')->default('general'); // Organizing settings
        $table->timestamps();
    });
    ```
- [ ] **Seeder**: `SettingsSeeder`
    -   `['key' => 'app_name', 'value' => 'SalesPro', 'type' => 'string']`
    -   `['key' => 'currency_symbol', 'value' => '$', 'type' => 'string']`

### 2. Backend Logic
- [ ] **Model**: `app/Models/Setting.php`. Add `$primaryKey = 'key'; $incrementing = false;`.
- [ ] **Controller**: `SettingController`
    -   `index()`: Return `Setting::all()->pluck('value', 'key')` to the view.
    -   `update(Request $request)`:
        -   Loop through `$request->all()` and update matching keys.
        -   **Cache**: Use `Cache::forever('settings', ...)` to avoid DB hits on every request. Clear cache on update.

### 3. Frontend Implementation
- [ ] **Settings Page**: `resources/js/Pages/Settings/Index.vue`
    -   Load props: `settings` (object).
    -   Form inputs for keys (App Name, Currency).
    -   Submit `router.post('/settings')` (or put).

### 4. Global Helper (Optional)
-   Create a helper function `settings('key')` to easily retrieve values in Blade/Controllers users the Cache.
