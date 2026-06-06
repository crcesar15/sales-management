# POS Navigation & Menu Structure

## Sidebar Menu Updates

### Current Structure

The current `useMenuItems.ts` has a placeholder "POS" menu item as a **top-level** entry pointing to `"home"`:

```typescript
{
  key: "pos",
  label: t("Point of Sale"),
  icon: "fa fa-cash-register",
  to: "home",  // ← Placeholder, needs update
}
```

### Updated Structure

The "Point of Sale" item should move inside the "Sales" section as the first item:

```typescript
// resources/js/Layouts/Composables/useMenuItems.ts

{
  key: "sales",
  label: t("Sales"),
  icon: "fa fa-receipt",
  items: [
    {
      key: "pos",
      label: t("Point of Sale"),
      icon: "fa fa-cash-register",
      to: "pos",
      can: "pos.access",
      routeUrl: route("pos"),
    },
    {
      key: "sales-orders",
      label: t("Sales Orders"),
      icon: "fa fa-file-invoice-dollar",
      to: "sales-orders",
      can: "sales_order.view",
      routeUrl: route("sales-orders"),
    },
    {
      key: "sales-customers",
      label: t("Customers"),
      icon: "fa fa-users",
      to: "customers",
      can: "customer.view",
      routeUrl: route("customers"),
    },
  ],
},
```

> **Note:** Remove the existing top-level POS placeholder item since it moves into the Sales group.

### Future: POS Management Subsection

After Task 01b (Cash Registers & Shifts) is implemented, add a management subsection:

```typescript
{
  key: "pos-management",
  label: t("POS Management"),
  icon: "fa fa-cash-register",
  items: [
    {
      key: "cash-registers",
      label: t("Cash Registers"),
      icon: "fa fa-till-window",
      to: "cash-registers",
      can: "cash_register.view",
      routeUrl: route("cash-registers"),
    },
    {
      key: "shifts",
      label: t("Shifts"),
      icon: "fa fa-clock-rotate-left",
      to: "shifts",
      can: "shift.view",
      routeUrl: route("shifts"),
    },
  ],
},
```

## Route Definitions

### Web Routes (`routes/web.php`)

```php
use App\Http\Controllers\Pos\PosController;

Route::middleware(['auth', 'verified'])->group(function () {
    // POS Interface - full-screen layout
    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    
    // Register selection (shown as modal/dialog, not a separate route)
    // Shift operations (open/close) are API calls, not web routes
});
```

### API Routes (`routes/api.php`)

```php
use App\Http\Controllers\Api\Pos\PosController as PosApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // POS session management
    Route::get('/pos/session', [PosApiController::class, 'session'])->name('api.v1.pos.session');
    Route::post('/pos/session/register', [PosApiController::class, 'selectRegister'])->name('api.v1.pos.session.register');
    Route::post('/pos/session/shift/open', [PosApiController::class, 'openShift'])->name('api.v1.pos.session.shift.open');
    Route::post('/pos/session/shift/close', [PosApiController::class, 'closeShift'])->name('api.v1.pos.session.shift.close');
    
    // Register list for selection dialog
    Route::get('/pos/registers', [PosApiController::class, 'registers'])->name('api.v1.pos.registers');
});
```

### Route Naming Convention

| Route Name | Path | Method | Purpose |
|------------|------|--------|---------|
| `pos` | `/pos` | GET | Main POS interface page |
| `api.v1.pos.session` | `/api/v1/pos/session` | GET | Get current POS session state |
| `api.v1.pos.session.register` | `/api/v1/pos/session/register` | POST | Select register for session |
| `api.v1.pos.session.shift.open` | `/api/v1/pos/session/shift/open` | POST | Open new shift |
| `api.v1.pos.session.shift.close` | `/api/v1/pos/session/shift/close` | POST | Close current shift |
| `api.v1.pos.registers` | `/api/v1/pos/registers` | GET | List registers for selection |

## Register Selection Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    User clicks "Point of Sale"                  │
│                         in sidebar menu                         │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│              Check: Does user have an open shift?               │
│                    (via GET /api/v1/pos/session)                │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                    ┌───────┴───────┐
                    │               │
                   YES             NO
                    │               │
                    │               ▼
                    │    ┌────────────────────────────────────────┐
                    │    │  Show Register Selection Dialog        │
                    │    │  ┌──────────────────────────────────┐  │
                    │    │  │ Select Register                  │  │
                    │    │  ├──────────────────────────────────┤  │
                    │    │  │ ○ REG-01 (Main) - Available      │  │
                    │    │  │ ○ REG-02 (Main) - In Use (John)  │  │
                    │    │  │ ○ REG-03 (Main) - Inactive       │  │
                    │    │  │                                  │  │
                    │    │  │ [Cancel]  [Select & Continue]    │  │
                    │    │  └──────────────────────────────────┘  │
                    │    └────────────────────────────────────────┘
                    │               │
                    │               │ User selects register
                    │               ▼
                    │    ┌────────────────────────────────────────┐
                    │    │  Check: Does selected register have  │
                    │    │  an open shift?                      │
                    │    └───────────────┬────────────────────────┘
                    │                    │
                    │            ┌───────┴───────┐
                    │            │               │
                    │           YES             NO
                    │            │               │
                    │            │               ▼
                    │            │    ┌────────────────────────┐
                    │            │    │ Prompt: Open New Shift │
                    │            │    │ ┌────────────────────┐ │
                    │            │    │ │ Opening Balance:   │ │
                    │            │    │ │ $ [____________]   │ │
                    │            │    │ │                    │ │
                    │            │    │ │ [Cancel] [Open]    │ │
                    │            │    │ └────────────────────┘ │
                    │            │    └────────────────────────┘ │
                    │            │               │
                    │            │               │ User enters amount
                    │            │               │ and confirms
                    │            ▼               ▼
                    │    ┌────────────────────────────────────────┐
                    │    │  Assign user to existing shift         │
                    │    │  OR create new shift via API           │
                    │    └────────────────────────────────────────┘
                    │                    │
                    │                    │ Success
                    │                    ▼
                    │    ┌────────────────────────────────────────┐
                    │    │     Close dialog, navigate to POS     │
                    │    │     router.visit(route('pos'))        │
                    │    └────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Navigate to POS Interface                    │
│                    (renders with PosLayout)                     │
└─────────────────────────────────────────────────────────────────┘
```

### Register Selection Edge Cases

The flow diagram above shows the happy path, but several edge cases must be handled:

1. **Register with open shift by another cashier**: Show "In Use by [Cashier Name]" — do NOT auto-assign. The register should appear in the list with status "In Use" and be non-selectable (similar to "Inactive" but with different messaging).

2. **User already has an open shift on a different register**: Show a warning dialog:
   > "You already have an open shift on Register REG-01. Please close that shift before opening a new one."
   
   Provide a button to navigate to the POS with their existing shift, or a button to close the existing shift first.

3. **Network error during register selection**: Show the error state in the dialog with a "Retry" button. Do not close the dialog.

4. **Session expired (401)**: The API client should intercept 401 responses and redirect to login. After re-authentication, attempt to restore the POS session.

## Exit POS Behavior

### "Exit POS" Button

Located in the shift bar (left side), the Exit button:

1. **Does NOT close the shift** — Shift remains open for when user returns
2. **Navigates to admin dashboard** — `router.visit(route('home'))`
3. **Preserves POS state** — Pinia store retains shift/register data
4. **Allows quick return** — User can re-enter POS and continue same shift

```typescript
function exitPos(): void {
  // Cart check will be added in Task 02 (POS Interface)
  // For now, just navigate back
  router.visit(route("home"));
}
```

> **Scope note:** The cart-has-items confirmation check is **not in scope for Task 01a** since the cart doesn't exist yet. It will be added in Task 02 (POS Interface).

### Browser Back Button

Inertia handles browser back/forward navigation natively. No manual `pushState` is needed:

- When user navigates to `/pos`, Inertia adds the page to browser history
- When user presses browser back, Inertia restores the previous page (admin dashboard)
- When user presses browser forward, Inertia restores the POS page

**Do NOT use manual `window.history.pushState()`** — it conflicts with Inertia's history management and can cause stale state issues.

## Error Handling

### API Error Handling Strategy

All API calls in `usePosClient` must handle errors consistently:

```typescript
// In usePosClient.ts
async function getSession(): Promise<PosSession> {
  try {
    const { data } = await api.get<PosSession>(route('api.v1.pos.session'));
    return data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      if (error.response?.status === 401) {
        // Session expired — redirect to login
        router.visit(route('login'));
        throw new Error('Session expired');
      }
      if (error.response?.status === 403) {
        throw new PosPermissionError('You do not have access to POS');
      }
    }
    throw new PosNetworkError('Failed to load session. Please check your connection.');
  }
}
```

### Error Types

| Error Type | User Feedback | Recovery |
|---|---|---|
| Network error | Toast: "Unable to connect. Check your network." | Retry button |
| 401 Unauthorized | Redirect to login | Re-authenticate |
| 403 Forbidden | Toast: "You don't have permission" | Contact admin |
| 422 Validation | Display field errors in form | Fix and retry |
| 500 Server error | Toast: "Something went wrong" | Retry button |
| Timeout | Toast: "Request timed out" | Retry button |

### Custom Error Classes

```typescript
// resources/js/Types/pos.ts

export class PosError extends Error {
  constructor(message: string, public code: string) {
    super(message);
    this.name = 'PosError';
  }
}

export class PosPermissionError extends PosError {
  constructor(message: string) {
    super(message, 'PERMISSION_DENIED');
    this.name = 'PosPermissionError';
  }
}

export class PosNetworkError extends PosError {
  constructor(message: string) {
    super(message, 'NETWORK_ERROR');
    this.name = 'PosNetworkError';
  }
}
```

### Session Expiry Handling

POS sessions are typically long-running (8+ hours). During that time:
- The Sanctum token could expire
- The user could be logged out from another device
- The browser session could expire

**Strategy:**
1. The Axios interceptor in `useApi` should detect 401 responses
2. Show a toast: "Your session has expired. Please log in again."
3. Redirect to the login page via `router.visit(route('login'))`
4. After re-authentication, Inertia will redirect back to `/pos`
5. On POS mount, check session again via `getSession()` to restore state

```typescript
// In PosLayout.vue or Pos/Index.vue
onMounted(async () => {
  try {
    const session = await posClient.getSession();
    posStore.setStore(session.store);
    posStore.setRegister(session.register);
    posStore.setShift(session.shift);
    posStore.setUserId(session.user.id);
  } catch (error) {
    if (error instanceof PosPermissionError) {
      toast.add({ severity: 'error', summary: t('Access Denied'), detail: error.message });
      router.visit(route('home'));
    } else if (error instanceof PosNetworkError) {
      toast.add({ severity: 'error', summary: t('Connection Error'), detail: error.message, life: 0 });
    }
  }
});
```

## Permission Gates

### Menu Visibility

The "Point of Sale" menu item only appears if user has `pos.access` permission:

```typescript
// In useMenuItems.ts
{
  key: "pos",
  label: t("Point of Sale"),
  icon: "fa fa-cash-register",
  to: "pos",
  can: "pos.access",  // ← Permission check
  routeUrl: route("pos"),
}
```

### Backend Authorization

```php
// In PosController.php
public function index(): InertiaResponse
{
    $this->authorize(PermissionsEnum::POS_ACCESS->value, auth()->user());
    
    // Render POS page
}
```

### Permission Matrix

| Permission | Enum Case | Value | Menu Item | Action | Form Request |
|---|---|---|---|---|---|
| `POS_ACCESS` | `case POS_ACCESS = 'pos.access'` | `pos.access` | Point of Sale | Enter POS interface | N/A |
| `SHIFT_OPEN` | `case SHIFT_OPEN = 'shift.open'` | `shift.open` | N/A | Open new shift | OpenShiftRequest |
| `SHIFT_CLOSE` | `case SHIFT_CLOSE = 'shift.close'` | `shift.close` | Shift bar | Close own shift | CloseShiftRequest |
| `SHIFT_VIEW` | `case SHIFT_VIEW = 'shift.view'` | `shift.view` | N/A | View shift details | N/A |
| `CASH_REGISTER_VIEW` | `case CASH_REGISTER_VIEW = 'cash_register.view'` | `cash_register.view` | Cash Registers (future) | View register list | N/A |

> **Note:** `pos.exit` was removed — exiting POS is always allowed if the user has `pos.access`. There is no scenario where a user should be allowed into POS but prevented from leaving.

## TypeScript Types

**Location:** `resources/js/Types/pos.ts`

```typescript
// Error classes
export class PosError extends Error {
  constructor(message: string, public code: string) {
    super(message);
    this.name = 'PosError';
  }
}

export class PosPermissionError extends PosError {
  constructor(message: string) {
    super(message, 'PERMISSION_DENIED');
    this.name = 'PosPermissionError';
  }
}

export class PosNetworkError extends PosError {
  constructor(message: string) {
    super(message, 'NETWORK_ERROR');
    this.name = 'PosNetworkError';
  }
}

// Data types
export interface CashRegister {
  id: number;
  name: string;
  code: string;
  store_id: number;
  is_default: boolean;
  status: "active" | "inactive";
  current_shift?: CashRegisterShift | null;
  created_at: string;
  updated_at: string;
}

export interface CashRegisterShift {
  id: number;
  shift_number: string;
  register_id: number;
  cashier_id: number;
  opening_balance: number;
  closing_balance: number | null;
  expected_closing_balance: number | null;
  status: "open" | "closed";
  opened_at: string;
  closed_at: string | null;
  register?: CashRegister;
  cashier?: {
    id: number;
    name: string;
    email: string;
  };
}

export interface PosSession {
  store: {
    id: number;
    name: string;
  };
  register: CashRegister | null;
  shift: CashRegisterShift | null;
  user: {
    id: number;
    name: string;
    email: string;
  };
}

export interface PosFilters {
  store_id?: number;
  register_id?: number;
}
```

> **Note:** `CashRegister.current_shift` is included so the register list API can indicate whether a register already has an open shift (and who the cashier is), enabling the "In Use by [Name]" display in the selection dialog.

## Composable: usePosClient

**Location:** `resources/js/Composables/usePosClient.ts`

```typescript
import { useApi } from "./useApi";
import { route } from "ziggy-js";
import type { PosSession, CashRegister } from "@/Types/pos";
import { PosError, PosPermissionError, PosNetworkError } from "@/Types/pos";
import axios from "axios";

export function usePosClient() {
  const api = useApi();

  async function getSession(): Promise<PosSession> {
    try {
      const { data } = await api.get<PosSession>(route("api.v1.pos.session"));
      return data;
    } catch (error) {
      handleApiError(error);
      throw error; // Re-throw after handling
    }
  }

  async function getRegisters(storeId?: number): Promise<CashRegister[]> {
    try {
      const { data } = await api.get<CashRegister[]>(
        route("api.v1.pos.registers"),
        { params: { store_id: storeId } }
      );
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function selectRegister(registerId: number): Promise<PosSession> {
    try {
      const { data } = await api.post<PosSession>(
        route("api.v1.pos.session.register"),
        { register_id: registerId }
      );
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function openShift(
    registerId: number,
    openingBalance: number
  ): Promise<PosSession> {
    try {
      const { data } = await api.post<PosSession>(
        route("api.v1.pos.session.shift.open"),
        { register_id: registerId, opening_balance: openingBalance }
      );
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function closeShift(
    shiftId: number,
    closingBalance?: number
  ): Promise<PosSession> {
    try {
      const { data } = await api.post<PosSession>(
        route("api.v1.pos.session.shift.close"),
        { shift_id: shiftId, closing_balance: closingBalance }
      );
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  return {
    getSession,
    getRegisters,
    selectRegister,
    openShift,
    closeShift,
  };
}

function handleApiError(error: unknown): never {
  if (axios.isAxiosError(error)) {
    const status = error.response?.status;
    
    if (status === 401) {
      // Session expired — redirect to login
      const { router } = require("@inertiajs/vue3");
      router.visit(route("login"));
      throw new PosError("Session expired", "UNAUTHORIZED");
    }
    
    if (status === 403) {
      throw new PosPermissionError(
        error.response?.data?.message || "You do not have permission for this action"
      );
    }
    
    if (status === 422) {
      // Validation errors — let the caller handle field-level errors
      throw error;
    }
    
    if (status && status >= 500) {
      throw new PosNetworkError("Something went wrong. Please try again.");
    }
  }
  
  // Network error or timeout
  throw new PosNetworkError("Unable to connect. Please check your network connection.");
}
```

## Translation Keys

All new translation keys that must be added to `resources/lang/en.json` and `resources/lang/es.json`:

```json
{
  "Point of Sale": "Punto de Venta",
  "Exit POS": "Salir del POS",
  "Store": "Tienda",
  "Register": "Caja",
  "Shift": "Turno",
  "Opened": "Abierto",
  "Open": "Abierto",
  "Closed": "Cerrado",
  "No shift": "Sin turno",
  "Close Shift": "Cerrar Turno",
  "Close shift": "Cerrar turno",
  "Are you sure you want to close this shift?": "¿Está seguro de que desea cerrar este turno?",
  "Yes, close shift": "Sí, cerrar turno",
  "Cancel": "Cancelar",
  "Select Register": "Seleccionar Caja",
  "Loading registers...": "Cargando cajas...",
  "No registers available": "No hay cajas disponibles",
  "Please contact your manager to set up a register.": "Por favor contacte a su administrador para configurar una caja.",
  "Failed to load registers": "Error al cargar las cajas",
  "Retry": "Reintentar",
  "Opening Balance": "Saldo de Apertura",
  "Select & Continue": "Seleccionar y Continuar",
  "Open Shift": "Abrir Turno",
  "POS requires a tablet or desktop": "El POS requiere una tableta o escritorio",
  "Please use a device with a screen width of at least 768px.": "Por favor use un dispositivo con un ancho de pantalla de al menos 768px.",
  "Return to Dashboard": "Volver al Panel",
  "Skip to main content": "Saltar al contenido principal",
  "In Use": "En Uso",
  "Inactive": "Inactivo",
  "Available": "Disponible",
  "You already have an open shift on Register :name.": "Ya tiene un turno abierto en la Caja :name.",
  "Please close that shift before opening a new one.": "Por favor cierre ese turno antes de abrir uno nuevo.",
  "Session expired. Please log in again.": "Sesión expirada. Por favor inicie sesión nuevamente.",
  "Connection Error": "Error de Conexión",
  "Access Denied": "Acceso Denegado",
  "Unable to connect. Please check your network connection.": "No se puede conectar. Por favor verifique su conexión de red."
}
```

## Implementation Checklist

### Menu Structure
- [ ] Move POS item from top-level into Sales group as first item
- [ ] Add `pos.access` permission check to POS menu item
- [ ] Add `routeUrl: route("pos")` to POS menu item
- [ ] Add future "POS Management" section (after 01b)
- [ ] Remove old top-level POS placeholder

### Routes
- [ ] Add web route for `/pos` in `routes/web.php`
- [ ] Add API routes for POS session management in `routes/api.php`
- [ ] Create `PosController` (web) in `app/Http/Controllers/Pos/`
- [ ] Create `PosApiController` (API) in `app/Http/Controllers/Api/Pos/`
- [ ] Create Form Requests for shift operations

### Permissions
- [ ] Add `POS_ACCESS`, `SHIFT_OPEN`, `SHIFT_CLOSE`, `SHIFT_VIEW`, `CASH_REGISTER_VIEW` to `PermissionsEnum.php`
- [ ] Update `PermissionSeeder.php` with new permissions
- [ ] Run `php artisan db:seed --class=PermissionSeeder`

### Register Selection
- [ ] Create register selection dialog component
- [ ] Implement session check on POS mount
- [ ] Implement register selection API integration
- [ ] Handle "In Use" registers (open shift by another cashier)
- [ ] Handle "user already has open shift on different register" edge case
- [ ] Implement shift opening flow
- [ ] Handle inactive registers (show but disable)
- [ ] Add error handling for API failures with retry

### Exit Navigation
- [ ] Implement "Exit POS" button in shift bar
- [ ] Cart check will be added in Task 02 (not 01a scope)
- [ ] Ensure shift is NOT closed on exit
- [ ] Let Inertia handle browser back/forward (no manual pushState)

### Error Handling
- [ ] Add error classes to `pos.ts` types file
- [ ] Implement API error handling in `usePosClient`
- [ ] Handle 401 (session expired) with redirect to login
- [ ] Handle 403 (permission denied) with toast
- [ ] Handle network errors with retry button
- [ ] Add session restoration flow after re-authentication

### Types & Composables
- [ ] Create TypeScript types in `resources/js/Types/pos.ts`
- [ ] Create `usePosClient` composable with error handling
- [ ] Create `usePosStore` Pinia store
- [ ] Create `usePosLayout` composable (simplified, no collapse)
- [ ] Wire up Pinia in `resources/js/app.ts`

### Translation Keys
- [ ] Add all POS translation keys to `resources/lang/en.json`
- [ ] Add all POS translation keys to `resources/lang/es.json`

### Testing
- [ ] Test menu visibility with/without permission
- [ ] Test register selection flow
- [ ] Test shift opening flow
- [ ] Test exit navigation
- [ ] Test browser back/forward (Inertia default behavior)
- [ ] Test error handling (network failures, session expiry)
- [ ] Test "In Use" register display
- [ ] Test "user already has open shift" edge case