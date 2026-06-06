# POS Navigation & Menu Structure

## Sidebar Menu Updates

### Current Structure

The current `useMenuItems.ts` has a placeholder "POS" menu item that points to `"home"`:

```typescript
{
  key: "pos",
  label: t("Point of Sale"),
  icon: "fa fa-cash-register",
  to: "home",  // ← Placeholder, needs update
}
```

### Updated Structure

The "Sales" section should be reorganized to include POS as the first item, followed by sales orders and customers:

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
}
```

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
}
```

## Route Definitions

### Web Routes (`routes/web.php`)

```php
use App\Http\Controllers\Pos\PosController;

Route::middleware(['auth', 'verified'])->group(function () {
    // POS Interface - full-screen layout
    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    
    // Register selection (shown as modal/dialog, not separate route)
    // Shift operations (open/close) are API calls
});
```

### API Routes (`routes/api.php`)

```php
use App\Http\Controllers\Api\Pos\PosController as PosApiController;

Route::middleware(['auth:sanctum'])->group(function () {
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
                    │    │  │ ○ REG-02 (Main) - In Use         │  │
                    │    │  │ ○ REG-03 (Main) - Inactive       │  │
                    │    │  │                                  │  │
                    │    │  │ [Cancel]  [Select & Continue]    │  │
                    │    │  └──────────────────────────────────┘  │
                    │    └────────────────────────────────────────┘
                    │               │
                    │               │ User selects register
                    │               ▼
                    │    ┌────────────────────────────────────────┐
                    │    │  Check: Does selected register have   │
                    │    │  an open shift?                       │
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
                    │    │     Assign user to existing shift     │
                    │    │     OR create new shift via API       │
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

## Exit POS Behavior

### "Exit POS" Button

Located in the shift bar (left side), the Exit button:

1. **Does NOT close the shift** — Shift remains open for when user returns
2. **Navigates to admin dashboard** — `router.visit(route('home'))`
3. **Preserves POS state** — Pinia store retains shift/register data
4. **Allows quick return** — User can re-enter POS and continue same shift

```typescript
function exitPos(): void {
  // Optional: Show confirmation if cart has items
  if (posStore.cart.hasItems) {
    const confirmed = confirm(t('You have items in your cart. Exit anyway?'));
    if (!confirmed) return;
  }
  
  // Navigate to home (or last visited admin page)
  router.visit(route('home'));
}
```

### Browser Back Button

The browser back button should also work:

```typescript
// In PosLayout.vue or Pos/Index.vue
onMounted(() => {
  // Push state to enable back navigation
  window.history.pushState({ fromPos: true }, '', route('pos'));
});

// Listen for popstate (back button)
onUnmounted(() => {
  // Cleanup if needed
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

| Permission | Menu Item | Action | Form Request |
|------------|-----------|--------|--------------|
| `pos.access` | Point of Sale | Enter POS interface | N/A |
| `pos.exit` | N/A | Exit POS (always allowed) | N/A |
| `shift.open` | N/A | Open new shift | OpenShiftRequest |
| `shift.close` | Shift bar | Close own shift | CloseShiftRequest |
| `shift.view` | N/A | View shift details | N/A |
| `cash_register.view` | Cash Registers (future) | View register list | N/A |

## TypeScript Types

**Location:** `resources/js/Types/pos.ts`

```typescript
export interface CashRegister {
  id: number;
  name: string;
  code: string;
  store_id: number;
  is_default: boolean;
  status: 'active' | 'inactive';
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
  status: 'open' | 'closed';
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

## Composable: usePosClient

**Location:** `resources/js/Composables/usePosClient.ts`

```typescript
import { useApi } from './useApi';
import { route } from 'ziggy-js';
import type { PosSession, CashRegister } from '@/Types/pos';

export function usePosClient() {
  const api = useApi();

  async function getSession(): Promise<PosSession> {
    const { data } = await api.get<PosSession>(route('api.v1.pos.session'));
    return data;
  }

  async function getRegisters(storeId?: number): Promise<CashRegister[]> {
    const { data } = await api.get<CashRegister[]>(
      route('api.v1.pos.registers'),
      { params: { store_id: storeId } }
    );
    return data;
  }

  async function selectRegister(registerId: number): Promise<PosSession> {
    const { data } = await api.post<PosSession>(
      route('api.v1.pos.session.register'),
      { register_id: registerId }
    );
    return data;
  }

  async function openShift(
    registerId: number,
    openingBalance: number
  ): Promise<PosSession> {
    const { data } = await api.post<PosSession>(
      route('api.v1.pos.session.shift.open'),
      { register_id: registerId, opening_balance: openingBalance }
    );
    return data;
  }

  async function closeShift(shiftId: number, closingBalance?: number): Promise<PosSession> {
    const { data } = await api.post<PosSession>(
      route('api.v1.pos.session.shift.close'),
      { shift_id: shiftId, closing_balance: closingBalance }
    );
    return data;
  }

  return {
    getSession,
    getRegisters,
    selectRegister,
    openShift,
    closeShift,
  };
}
```

## Implementation Checklist

### Menu Structure
- [ ] Update `useMenuItems.ts` with new "Sales" section structure
- [ ] Add `pos.access` permission check to POS menu item
- [ ] Add future "POS Management" section (after 01b)

### Routes
- [ ] Add web route for `/pos` in `routes/web.php`
- [ ] Add API routes for POS session management
- [ ] Create `PosController` (web) and `PosApiController` (API)

### Register Selection
- [ ] Create register selection dialog component
- [ ] Implement session check on POS mount
- [ ] Implement register selection API integration
- [ ] Implement shift opening flow
- [ ] Handle inactive registers (show but disable)

### Exit Navigation
- [ ] Implement "Exit POS" button in shift bar
- [ ] Add confirmation if cart has items (optional)
- [ ] Ensure shift is NOT closed on exit
- [ ] Test browser back button behavior

### Types & Composables
- [ ] Create TypeScript types in `resources/js/Types/pos.ts`
- [ ] Create `usePosClient` composable
- [ ] Create `usePosStore` Pinia store (see layout.md)

### Testing
- [ ] Test menu visibility with/without permission
- [ ] Test register selection flow
- [ ] Test shift opening flow
- [ ] Test exit navigation
- [ ] Test browser back button
