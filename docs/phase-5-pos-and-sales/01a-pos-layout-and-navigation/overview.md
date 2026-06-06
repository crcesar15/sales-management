# Task 01a — POS Layout & Navigation

## What

The Point of Sale (POS) layout is a specialized, full-screen interface designed for cashiers to process transactions efficiently. Unlike the standard admin dashboard layout with sidebar navigation, the POS layout maximizes screen real estate for the checkout workflow while providing essential shift information and a quick escape route back to the admin interface.

This task covers:
- Creating the `PosLayout` component (full-screen, no sidebar)
- Creating the `PosShiftBar` component (fixed top bar with shift info)
- Updating the sidebar menu to include POS entry point
- Implementing register selection flow before entering POS
- Defining navigation patterns between admin and POS interfaces

## Why

A cashier-facing POS has fundamentally different requirements than admin/management screens:

1. **Screen Real Estate** — Cashiers need maximum space for product search, cart display, and payment panels. A sidebar wastes valuable horizontal space.

2. **Task Focus** — During checkout, cashiers should not be distracted by navigation to other modules. The POS is a "destination" interface.

3. **Shift Accountability** — Every sale must be linked to an open shift for cash tracking. The shift bar provides constant visibility of shift status.

4. **Speed** — Keyboard-first workflow (barcode scanners, shortcut keys) requires a layout optimized for rapid interactions, not mouse-driven navigation.

5. **Escape Route** — Cashiers or managers may need to quickly return to the admin interface (e.g., to look up customer info, check inventory). The "Exit POS" button provides this without closing the shift.

## Prerequisites

Before implementing this task, the following must be set up:

1. **Pinia must be wired into the app** — Pinia is listed as a dependency (`"pinia": "^3.0.4"`) but is not yet initialized in `resources/js/app.ts`. Add:
   ```typescript
   import { createPinia } from 'pinia';
   app.use(createPinia());
   ```
   This must be done before any `usePosStore` calls will work.

2. **Permissions must be seeded** — New permission cases must be added to `PermissionsEnum.php` and seeded via `PermissionSeeder.php`, then run:
   ```bash
   php artisan db:seed --class=PermissionSeeder
   ```

3. **Translation keys must be added** — All new `t()` keys must be added to both `resources/lang/en.json` and `resources/lang/es.json`.

## Requirements

### Layout Requirements
- Full-screen layout with no sidebar or standard navigation bar
- Fixed shift info bar at top (56px height)
- Content area scrolls independently below shift bar
- Works on desktop and tablet viewports (≥768px)
- Shows "unsupported" message on screens < 768px (with link back to dashboard)
- Dark mode support (inherits from app-wide theme via `useLayout()`)

### Navigation Requirements
- POS entry point in sidebar under "Sales" section
- Permission-gated: requires `pos.access` permission
- Register selection required before entering POS if no open shift exists
- "Exit POS" button in shift bar returns to admin dashboard
- Browser back button also returns to admin (standard Inertia behavior — no manual `pushState` needed)

### Shift Bar Requirements
- Fixed/sticky position at top of viewport
- Displays: store name, register name, shift status, opening balance
- Shows cashier name (current user)
- "Close Shift" button visible only when shift is open and user is the cashier
- "Exit POS" button (hamburger menu icon) always visible

### Register Selection Requirements
- Dialog appears when user clicks "Point of Sale" without an open shift
- Lists available registers filtered by user's assigned store
- Inactive registers are shown but cannot be selected
- If a register already has an open shift by a **different** cashier, show it as "In Use" (not auto-assignable)
- If the user already has an open shift on a different register, prompt them to close it first
- If no open shift exists for the selected register, prompt user to open new shift (requires `shift.open` permission)
- On successful selection/opening, navigate to POS interface

### Error Handling Requirements
- API failures (network errors, 500s) must show user-friendly error messages with retry option
- Session expiry (401) must redirect to login, preserving POS state where possible
- Register loading failures must show error state with "Retry" button
- Shift open/close failures must not leave the UI in an inconsistent state

### Accessibility Requirements
- WCAG 2.1 AA compliant
- Full keyboard navigation support
- Visible focus indicators (2px ring)
- Screen reader announcements for shift status changes
- Minimum 44×44px touch targets
- Logical tab order (left-to-right, top-to-bottom)

## Acceptance Criteria

### Layout
- [ ] POS route (`/pos`) renders with `PosLayout`, not `AppLayout`
- [ ] Shift bar is fixed at top with `z-index: 1000`
- [ ] Content area scrolls independently below shift bar
- [ ] Layout supports dark mode (inherits from `useLayout()` composable, uses Tailwind `dark:` variant)
- [ ] Viewports < 768px show "POS requires tablet or desktop" message with link back to dashboard
- [ ] Viewport check is reactive (updates on window resize)

### Navigation
- [ ] "Point of Sale" menu item appears under "Sales" section in sidebar
- [ ] Menu item requires `pos.access` permission
- [ ] Clicking "Point of Sale" without open shift shows register selection dialog
- [ ] Register selection dialog filters by user's store
- [ ] "Exit POS" button navigates to admin dashboard via `router.visit(route('home'))`
- [ ] Exit does NOT close the shift (shift remains open)

### Shift Bar
- [ ] Store name, register name, shift status displayed correctly
- [ ] Opening balance formatted using `useCurrencyFormatter()` (not hardcoded USD)
- [ ] "Close Shift" button visible only when shift is open
- [ ] "Close Shift" button requires `shift.close` permission
- [ ] Shift status changes announced to screen readers (`aria-live="polite"`)

### Register Selection
- [ ] Dialog lists all active registers for store
- [ ] Inactive registers shown with disabled state
- [ ] Registers with open shifts by other cashiers shown as "In Use" (not selectable)
- [ ] If user has an open shift on another register, show warning and prompt to close it first
- [ ] Selecting register without open shift prompts to open new shift
- [ ] Opening shift requires `shift.open` permission
- [ ] On success, dialog closes and POS interface loads

### Error Handling
- [ ] Network failures show error toast with retry option
- [ ] Session expiry (401) redirects to login
- [ ] Register loading failure shows error state in dialog
- [ ] Shift open/close failure shows error and preserves UI state

### Accessibility
- [ ] All interactive elements have visible focus indicators
- [ ] Tab order follows visual layout
- [ ] Escape key closes dialogs
- [ ] Shift bar has `role="banner"` and `aria-label`
- [ ] Touch targets meet 44×44px minimum
- [ ] Contrast ratio ≥ 4.5:1 for all text

### Responsive
- [ ] Layout renders correctly at 768px (tablet portrait)
- [ ] Layout renders correctly at 1024px (desktop)
- [ ] Shift bar remains fixed on scroll
- [ ] Content adapts to available width

## Permissions

| Permission | Enum Case | Value | Scope |
|---|---|---|---|
| `POS_ACCESS` | `case POS_ACCESS = 'pos.access'` | `pos.access` | Enter POS interface |
| ~~`pos.exit`~~ | ~~Removed~~ | — | Exit is always allowed if user can access POS |
| `SHIFT_OPEN` | `case SHIFT_OPEN = 'shift.open'` | `shift.open` | Open new shift on register |
| `SHIFT_CLOSE` | `case SHIFT_CLOSE = 'shift.close'` | `shift.close` | Close own shift |
| `SHIFT_VIEW` | `case SHIFT_VIEW = 'shift.view'` | `shift.view` | View shift details in shift bar |
| `CASH_REGISTER_VIEW` | `case CASH_REGISTER_VIEW = 'cash_register.view'` | `cash_register.view` | View register list in selection dialog |

> **Note:** `pos.exit` was removed because if a user has `pos.access`, they can always exit. There's no scenario where a user should be allowed into POS but prevented from leaving.

## Dependencies

- **Cash Registers & Shifts (01b)** — Register and shift data models (shift CRUD is 01b scope; 01a only needs read access to shifts)
- **Spatie Permission** — Permission gates for POS access and shift operations
- **Pinia** — Must be initialized in `app.ts` before POS store can work
- **PrimeVue** — Dialog, Button, Toast, ConfirmationService components
- **Tailwind CSS** — Layout utilities, dark mode via `dark:` variant
- **Vue Router / Inertia** — Navigation between admin and POS
- **`useLayout()` composable** — Dark mode inheritance (located at `resources/js/Layouts/Components/Composables/useLayout.ts`)
- **`useCurrencyFormatter()` composable** — Currency display (located at `resources/js/Composables/useCurrencyFormatter.ts`)

## File Locations

| File | Path | Purpose |
|------|------|---------|
| Layout | `resources/js/Layouts/Components/PosLayout.vue` | Main POS layout wrapper |
| Shift Bar | `resources/js/Layouts/Components/PosShiftBar.vue` | Fixed top bar component |
| Register Dialog | `resources/js/Pages/Pos/Components/RegisterSelectDialog.vue` | Register selection modal |
| Menu Update | `resources/js/Layouts/Composables/useMenuItems.ts` | Add POS menu item |
| Types | `resources/js/Types/pos.ts` | TypeScript interfaces |
| Composable | `resources/js/Composables/usePosClient.ts` | API client for POS operations |
| Store | `resources/js/Composables/usePosStore.ts` | Pinia store for POS session state |
| Enums | `app/Enums/PermissionsEnum.php` | Add POS/shift permission cases |
| Seeder | `database/seeders/PermissionSeeder.php` | Seed new permissions |

## Relationship to Other Modules

```
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 5: POS & SALES                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  01a — POS Layout & Navigation (THIS MODULE)                   │
│  │                                                              │
│  │  Provides: Layout framework, navigation, shift bar          │
│  │  ↓                                                          │
│  ├────────────────────────────────────────────────────────────  │
│  │                                                              │
│  01 — Customer Management                                      │
│  │  Uses: POS layout for customer search during checkout       │
│  │                                                              │
│  01b — Cash Registers & Shifts                                 │
│  │  Provides: Register/shift CRUD, shift open/close logic     │
│  │  Used by: POS layout (shift bar), POS interface (gate)     │
│  │  Note: 01a only needs READ access to shifts; 01b adds CRUD │
│  │                                                              │
│  02 — POS Interface                                            │
│  │  Uses: POS layout, shift bar, register selection            │
│  │  Provides: Product search, cart, payment, checkout          │
│  │  Note: Cart/checkout features are 02 scope, not 01a       │
│  │                                                              │
│  03 — Sales Orders                                             │
│  │  Uses: POS interface creates orders on checkout             │
│  │                                                              │
│  └─────────────────────────────────────────────────────────────┘
```

## Notes

- POS is a **destination** interface — users enter to process transactions and exit to return to admin
- Shift bar is **always visible** during POS session (fixed position)
- Register selection is a **one-time gate** per session — once a shift is open, user stays in POS
- Exit POS does **not** close the shift — cashier can re-enter POS and continue same shift
- POS does **not** support mobile viewports (< 768px) — show informative message with a link back to the dashboard
- All monetary values formatted using `useCurrencyFormatter()` composable (respects user's currency settings)
- Cart-related logic (checking for items in cart before exit) is **not in scope for 01a** — it will be added in Task 02 (POS Interface)
- Shift bar **collapse feature** is removed from 01a scope — the bar stays fixed at 56px. Collapse can be added in a future iteration if needed.