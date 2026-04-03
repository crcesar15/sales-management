# Routes & API Design Rules

## Web Routes (`routes/web.php`)

- All web routes render Inertia pages — no Blade views for application pages
- Use resource-style naming: `users.index`, `users.create`, `users.edit`
- Auth routes use `guest` middleware, protected routes use `auth` middleware
- RESTful actions only: `index`, `create`, `store`, `edit`, `update`, `destroy`

## API Routes (`routes/api.php`)

- Grouped under `v1` prefix: `Route::prefix('v1')`
- Named with `api.v1.` prefix: `api.v1.users.index`, `api.v1.users.store`
- All protected by `auth:sanctum` middleware
- Standard CRUD actions: `index`, `show`, `store`, `update`, `destroy`
- Include `restore` endpoint for soft-deleted resources

## API Response Format

- Single resources return via `JsonResource`:
  ```php
  return (new UserResource($user))->response()->setStatusCode(201);
  ```
- Collections return via `ResourceCollection` with pagination meta:
  ```json
  {
    "data": [...],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 10,
      "total": 48
    }
  }
  ```

## Frontend Route Usage

- Use Ziggy's `route()` function for all URL generation:
  ```typescript
  import { route } from "ziggy-js";
  route("api.v1.users.store")       // API call URL
  route("users")                     // Web page URL
  route("users.edit", user.id)       // Web page with param
  ```
- Named routes only — never hardcode URLs
