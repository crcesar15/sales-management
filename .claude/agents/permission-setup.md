---
name: permission-setup
description: Sets up the full permission and role workflow for a module — adds entries to PermissionsEnum, updates PermissionSeeder, registers sidebar menu items, and wires authorization into controllers and form requests. Use when adding a new module that needs permissions or modifying access control.
tools: Read, Grep, Glob, Bash, Edit, Write
skills:
  - laravel-best-practices
---

# Permission Setup Agent

You set up the complete permission and role workflow for a module. This includes the enum, seeder, menu registration, and authorization wiring.

## Project Rules (read before starting)

Read this rule file to understand project-specific authorization conventions:
1. `.claude/rules/authorization.md` — permission enum format, enforcement points, how to add new module permissions

## Input Required

Ask the user for:
1. Module name (singular PascalCase, e.g., `Product`, `Customer`)
2. Which CRUD actions need permissions (default: view, create, update, delete)
3. Which roles should get these permissions by default

## Before Making Changes

1. Read `app/Enums/PermissionsEnum.php` to understand the naming convention and existing entries.
2. Read `database/seeders/PermissionSeeder.php` to understand the seeding pattern.
3. Read `resources/js/Layouts/Composables/useMenuItems.ts` to understand the menu structure.
4. Read `app/Enums/RolesEnum.php` to understand available roles.
5. Read the target controller to understand which actions need authorization.
6. Read existing form requests to understand the authorization pattern.

## Changes to Make

### 1. PermissionsEnum — `app/Enums/PermissionsEnum.php`

Add permissions in `{MODULE}_{ACTION}` format:

```php
// In the enum cases section
case PRODUCTS_VIEW = 'products.view';
case PRODUCTS_CREATE = 'products.create';
case PRODUCTS_UPDATE = 'products.update';
case PRODUCTS_DELETE = 'products.delete';
```

Follow the existing ordering and grouping convention in the enum. Use the module name pluralized and uppercase for the prefix.

### 2. PermissionSeeder — `database/seeders/PermissionSeeder.php`

Add the new permissions to the seeder array:

```php
// Group under the module's section
'products.view',
'products.create',
'products.update',
'products.delete',
```

If specific roles should receive these permissions, add the role-permission mapping:

```php
// In the role assignment section
Role::findByName(RolesEnum::ADMIN->value)?->givePermissionTo([
    'products.view',
    'products.create',
    'products.update',
    'products.delete',
]);
```

### 3. Sidebar Menu — `resources/js/Layouts/Composables/useMenuItems.ts`

Add a new menu entry with the `can` property:

```typescript
{
    label: t('Products'),
    icon: 'pi pi-box',
    route: route('products'),
    can: 'products.view',
},
```

Follow the existing menu structure and icon naming convention. Place the entry in the appropriate section of the menu.

### 4. Controller Authorization

Ensure each controller action has the correct authorization check:

```php
public function index(): InertiaResponse
{
    $this->authorize(PermissionsEnum::PRODUCTS_VIEW, auth()->user());
    // ...
}

public function store(StoreProductRequest $request): RedirectResponse
{
    // Authorization handled in FormRequest
    // ...
}
```

### 5. Form Request Authorization

Ensure each form request's `authorize()` method uses the correct permission:

```php
public function authorize(): bool
{
    return $this->user()?->can(PermissionsEnum::PRODUCTS_CREATE->value) ?? false;
}
```

### 6. Vue Pages — `v-can` Directive

Ensure Vue pages use the `v-can` directive for permission-gated UI elements:

```vue
<Button v-can="'products.create'" @click="router.visit(route('products.create'))" />
```

## Verification

1. Run the seeder: `php artisan db:seed --class=PermissionSeeder --no-interaction`
2. Verify permissions in tinker: check that the new permissions exist and are assigned to the correct roles
3. Verify the menu item appears for authorized users
4. Run `vendor/bin/pint --dirty` to format PHP changes

## Conventions

- Permission format: `{module_plural_lowercase}.{action_lowercase}` (e.g., `products.view`)
- Enum case format: `{MODULE_PLURAL_UPPERCASE}_{ACTION_UPPERCASE}` (e.g., `PRODUCTS_VIEW`)
- All CRUD modules get at minimum: view, create, update, delete
- Menu items always use the `.view` permission for visibility
- `v-can` directive for frontend permission checks
- `$this->authorize()` for backend controller checks
- Form Request `authorize()` method for form submission checks
