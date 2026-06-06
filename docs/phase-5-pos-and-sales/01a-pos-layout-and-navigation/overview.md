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

## Requirements

### Layout Requirements
- Full-screen layout with no sidebar or standard navigation bar
- Fixed shift info bar at top (56px height)
- Content area scrolls independently below shift bar
- Works on desktop and tablet viewports (≥768px)
- Shows "unsupported" message on screens < 768px
- Dark mode support (inherits from app-wide theme)

### Navigation Requirements
- POS entry point in sidebar under "Sales" section
- Permission-gated: requires `pos.access` permission
- Register selection required before entering POS if no open shift exists
- "Exit POS" button in shift bar returns to admin dashboard
- Browser back button also returns to admin (standard behavior)

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
- If selected register has an open shift, auto-assign user to it
- If no open shift, prompt user to open new shift (requires `shift.open` permission)
- On successful selection/opening, navigate to POS interface

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
- [ ] Layout supports dark mode (inherits from `useLayout()` composable)
- [ ] Viewports < 768px show "POS requires tablet or desktop" message

### Navigation
- [ ] "Point of Sale" menu item appears under "Sales" section in sidebar
- [ ] Menu item requires `pos.access` permission
- [ ] Clicking "Point of Sale" without open shift shows register selection dialog
- [ ] Register selection dialog filters by user's store
- [ ] "Exit POS" button calls `router.visit(route('home'))`
- [ ] Exit does NOT close the shift (shift remains open)

### Shift Bar
- [ ] Store name, register name, shift status displayed correctly
- [ ] Opening balance formatted as currency
- [ ] "Close Shift" button visible only when shift is open
- [ ] "Close Shift" button requires `shift.close` permission
- [ ] Shift status changes announced to screen readers (`aria-live="polite"`)

### Register Selection
- [ ] Dialog lists all active registers for store
- [ ] Inactive registers shown with disabled state
- [ ] Selecting register with open shift assigns user to that shift
- [ ] Selecting register without open shift prompts to open new shift
- [ ] Opening shift requires `shift.open` permission
- [ ] On success, dialog closes and POS interface loads

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

| Permission | Scope |
|---|---|
| `pos.access` | Enter POS interface |
| `pos.exit` | Exit POS (always allowed if can access) |
| `shift.open` | Open new shift on register |
| `shift.close` | Close own shift |
| `shift.view` | View shift details in shift bar |
| `cash_register.view` | View register list in selection dialog |

## Dependencies

- **Cash Registers & Shifts (01b)** — Register and shift data models
- **Spatie Permission** — Permission gates for POS access and shift operations
- **PrimeVue** — Dialog, Button, Toast components
- **Tailwind CSS** — Layout utilities, dark mode support
- **Vue Router / Inertia** — Navigation between admin and POS
- **`useLayout()` composable** — Dark mode inheritance

## File Locations

| File | Path | Purpose |
|------|------|---------|
| Layout | `resources/js/Layouts/Components/PosLayout.vue` | Main POS layout wrapper |
| Shift Bar | `resources/js/Layouts/Components/PosShiftBar.vue` | Fixed top bar component |
| Register Dialog | `resources/js/Pages/Pos/Components/RegisterSelectDialog.vue` | Register selection modal |
| Menu Update | `resources/js/Layouts/Composables/useMenuItems.ts` | Add POS menu item |
| Types | `resources/js/Types/pos.ts` | TypeScript interfaces |
| Composable | `resources/js/Composables/usePosClient.ts` | API client for POS operations |

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
│  │  Provides: Register/shift CRUD, shift open/close logic      │
│  │  Used by: POS layout (shift bar), POS interface (gate)     │
│  │                                                              │
│  02 — POS Interface                                            │
│  │  Uses: POS layout, shift bar, register selection            │
│  │  Provides: Product search, cart, payment, checkout          │
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
- POS does **not** support mobile viewports (< 768px) — show informative message instead
- All monetary values formatted using `useCurrencyFormatter()` composable
