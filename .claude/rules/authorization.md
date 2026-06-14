# Authorization & Permissions Rules

## Permission Enum

- Permissions are defined in `app/Enums/PermissionsEnum.php` using `{MODULE}_{ACTION}` format
- Standard actions per module: `VIEW`, `CREATE`, `EDIT`, `DELETE`, `RESTORE`
- Example: `PRODUCTS_VIEW`, `USERS_CREATE`, `ROLES_EDIT`, `BRANDS_DELETE`

## Adding a New Module's Permissions

1. Add cases to `PermissionsEnum`:
   ```php
   case STORES_VIEW = 'store.view';
   case STORES_CREATE = 'store.create';
   case STORES_EDIT = 'store.edit';
   case STORES_DELETE = 'store.delete';
   case STORES_RESTORE = 'store.restore';
   ```
2. Register in `database/seeders/PermissionSeeder.php`
3. Add sidebar entry in `resources/js/Layouts/Composables/useMenuItems.ts` with `can` property
4. Run `php artisan db:seed --class=PermissionSeeder`

## Enforcement Points

### Backend — Web Controllers

```php
$this->authorize(PermissionsEnum::USERS_VIEW);
```
Note: No user parameter needed — uses the authenticated user automatically.

### Backend — API Controllers

```php
$this->authorize(PermissionsEnum::USERS_VIEW->value, auth()->user());
```
Note: Requires `->value` and explicit user parameter. This is different from Web controllers.

### Backend — Form Requests

```php
public function authorize(): bool
{
    return $this->user()?->can(PermissionsEnum::USERS_CREATE->value) ?? false;
}
```

### Backend — Policies

All policies are `final class` using `PermissionsEnum` for authorization. Standard methods: `viewAny`, `view`, `create`, `update`, `delete`, `restore`. Auto-discovered (not registered in `AuthServiceProvider`). No ownership-based logic except `UserPolicy` which adds `$user->id !== $target->id` checks.

### Frontend — Vue Templates

```vue
<Button v-can="'user.create'" :label="t('Add User')" />
```

The `v-can` directive is **reactive** — it watches permissions from `useAuth()` and dynamically shows/hides elements. Supports:
- Single permission: `v-can="'user.create'"`
- Any permission: `v-can="['user.create', 'user.edit']"`
- Always show: `v-can="true"`

### Frontend — Composables

```typescript
const { can, canAny, canAll } = useAuth();
if (can('user.create')) { /* ... */ }
```

### Frontend — Sidebar Menu

```typescript
{
  key: "users",
  label: t("Users"),
  icon: "fa fa-users",
  to: "users",
  can: "user.view",
}
```

## Roles

- Defined in `app/Enums/RolesEnum.php` with descriptive values
- Current roles: `ADMIN` (super administrator), `SALESMAN` (salesman)
- Role-permission assignments are managed via `spatie/laravel-permission`