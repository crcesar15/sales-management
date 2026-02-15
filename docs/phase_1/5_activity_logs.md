# Task: Activity Logs Integration

## Goal
Track changes to critical models using `spatie/laravel-activitylog`.

## Technical Implementation

### 1. Installation & Config
- [ ] **Install**: `composer require spatie/laravel-activitylog`.
- [ ] **Publish**: `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"`.
- [ ] **Migrate**: `php artisan migrate`.

### 2. Model Configuration
- [ ] **User Model**:
    ```php
    use Spatie\Activitylog\Traits\LogsActivity;
    use Spatie\Activitylog\LogOptions;

    class User extends Authenticatable {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions {
            return LogOptions::defaults()
                ->logFillable() // Log all fillable attributes
                ->logOnlyDirty(); // Only changes
        }
    }
    ```
- [ ] **Other Models**: Repeat for `Role`, `Setting`, (and later `Product`, `Order`).

### 3. UI Implementation (Phase 1)
- [ ] **User Profile Tab**: Add an "Activity" tab to the User Edit page.
- [ ] **Backend Endpoint**:
    -   `$user->activities()->latest()->get()`.
- [ ] **Display**:
    -   Table showing: "Event" (updated), "Caused By" (Admin), "Properties" (Old vs New values).
    -   Format the `properties` JSON column nicely in the UI.

### 4. Verification
-   Go to User Edit page, change "John Doe" to "John Smith".
-   Check database `activity_log` table for a new row recording the change.
