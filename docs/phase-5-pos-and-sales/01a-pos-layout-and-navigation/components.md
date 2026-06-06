# POS Components

## Reusable Components

This document defines the shared components used by the POS layout and interface.

---

## PosShiftBar.vue

**Location:** `resources/js/Layouts/Components/PosShiftBar.vue`

> **Note:** The full implementation code is in `layout.md`. This document covers the component's API contract (props, events, slots).

### Purpose

Fixed top bar that displays shift information and provides navigation controls for the POS interface.

### Props

```typescript
interface PosShiftBarProps {
  /** Current store information */
  store?: {
    id: number;
    name: string;
  };
  
  /** Current register information */
  register?: {
    id: number;
    name: string;
    code: string;
  };
  
  /** Current shift information (null if no shift) */
  shift?: {
    id: number;
    shift_number: string;
    cashier_id: number;
    cashier_name: string;
    opening_balance: number;
    status: "open" | "closed";
    opened_at: string;
  } | null;
  
  /** Current user ID (to check if user is the cashier) */
  userId?: number | null;
}
```

> **Implementation note:** The current implementation reads from `usePosStore` directly rather than receiving all data via props. The store, register, and shift data come from Pinia. Props are available for overrides or testing.

### Slots

| Slot Name | Props | Description |
|-----------|-------|-------------|
| `#shift-status` | `{ shift: Shift }` | Override default shift status display |
| `#actions` | N/A | Additional actions on the right side |

### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `exit` | N/A | User clicked "Exit POS" button |
| `close-shift` | N/A | User clicked "Close Shift" button |
| `shift-click` | `{ shift: Shift }` | User clicked on shift info (for details) |

### Key Implementation Details

1. **Uses `useConfirm()` from PrimeVue** instead of native `confirm()` for the shift close confirmation dialog
2. **Uses `useCurrencyFormatter()`** for formatting opening balance (respects app currency settings)
3. **All `aria-label` attributes use `:aria-label` binding** (not plain `aria-label`) to ensure translated strings render correctly
4. **Decorative icons have `aria-hidden="true"`** with accompanying `sr-only` labels
5. **Badge uses `value` prop** (PrimeVue 4 convention) instead of `label` prop for text content

---

## RegisterSelectDialog.vue

**Location:** `resources/js/Pages/Pos/Components/RegisterSelectDialog.vue`

### Purpose

Modal dialog for selecting a cash register before entering the POS interface.

### Props

```typescript
interface RegisterSelectDialogProps {
  /** Dialog visibility state */
  visible: boolean;
  
  /** List of available registers */
  registers: CashRegister[];
  
  /** Loading state (during API calls) */
  loading?: boolean;
  
  /** Currently selected register ID */
  selectedRegisterId?: number | null;
  
  /** Store name for context */
  storeName?: string;
}
```

### CashRegister Type (Enhanced)

```typescript
interface CashRegister {
  id: number;
  name: string;
  code: string;
  store_id: number;
  is_default: boolean;
  status: "active" | "inactive";
  // Enhanced for selection dialog:
  current_shift?: {
    id: number;
    cashier_id: number;
    cashier_name: string;
    status: "open" | "closed";
  } | null;
  created_at: string;
  updated_at: string;
}
```

> **Note:** `current_shift` is included so the dialog can show "In Use by [Cashier Name]" for registers with open shifts by other users. This is not a navigable shift — it's display-only info.

### Slots

| Slot Name | Props | Description |
|-----------|-------|-------------|
| `#header` | N/A | Override dialog header |
| `#register-item` | `{ register: CashRegister, selected: boolean }` | Custom register item rendering |
| `#footer` | N/A | Override dialog footer |

### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `update:visible` | `boolean` | Dialog visibility change |
| `select` | `{ registerId: number }` | User selected a register |
| `open-shift` | `{ registerId: number, openingBalance: number }` | User wants to open new shift |
| `cancel` | N/A | User cancelled selection |

### Key Implementation Details

1. **"In Use" registers are shown but not selectable**: If a register has `current_shift` with status "open" and the cashier is NOT the current user, show it as "In Use by [Name]" and disable the radio button
2. **"Inactive" registers are shown with disabled state**: Grayed out, radio button disabled, "Inactive" label
3. **Opening balance input uses VeeValidate + Yup**: Follows the project's form validation pattern
4. **Error handling with retry**: Network failures show an error state with a "Retry" button that re-fetches the register list
5. **Empty state**: If the user's store has no registers at all, show a message directing them to contact their manager

### Register Status Logic

```typescript
function getRegisterStatus(register: CashRegister, currentUserId: number): RegisterStatus {
  if (register.status === "inactive") {
    return { state: "inactive", selectable: false, label: t("Inactive") };
  }
  if (register.current_shift?.status === "open") {
    if (register.current_shift.cashier_id === currentUserId) {
      // User's own open shift — they can continue it
      return { state: "own-shift", selectable: true, label: t("Continue Shift") };
    }
    // Another user's open shift — not selectable
    return {
      state: "in-use",
      selectable: false,
      label: t("In Use by {name}", { name: register.current_shift.cashier_name }),
    };
  }
  return { state: "available", selectable: true, label: t("Available") };
}
```

### User Already Has Open Shift Edge Case

If the user already has an open shift on a **different** register, the dialog should show a warning instead of the register list:

```vue
<div v-if="userHasOpenShiftElsewhere" class="text-center py-8">
  <i class="fa fa-exclamation-triangle text-4xl text-yellow-500 mb-4" aria-hidden="true" />
  <h3 class="text-lg font-semibold mb-2">
    {{ t("You already have an open shift on Register :name.", { name: existingShiftRegister }) }}
  </h3>
  <p class="text-surface-500 dark:text-surface-400 mb-4">
    {{ t("Please close that shift before opening a new one.") }}
  </p>
  <div class="flex gap-4 justify-center">
    <Button :label="t('Go to My Shift')" @click="navigateToExistingShift" />
    <Button :label="t('Close My Shift')" severity="danger" @click="closeExistingShift" />
  </div>
</div>
```

### Example Usage

```vue
<template>
  <RegisterSelectDialog
    v-model:visible="dialogVisible"
    :registers="registers"
    :loading="isLoading"
    :store-name="store.name"
    @select="handleRegisterSelect"
    @open-shift="handleOpenShift"
    @cancel="handleCancel"
  />
</template>

<script setup lang="ts">
const dialogVisible = ref(false);
const registers = ref<CashRegister[]>([]);
const isLoading = ref(false);

async function handleRegisterSelect({ registerId }: { registerId: number }) {
  // Proceed with selection — register has existing open shift by this user
  const session = await posClient.selectRegister(registerId);
  posStore.setRegister(session.register);
  posStore.setShift(session.shift);
  dialogVisible.value = false;
}

async function handleOpenShift({ registerId, openingBalance }: { registerId: number; openingBalance: number }) {
  // Open new shift on selected register
  const session = await posClient.openShift(registerId, openingBalance);
  posStore.setRegister(session.register);
  posStore.setShift(session.shift);
  dialogVisible.value = false;
}

function handleCancel() {
  // Navigate back to home if user cancels
  router.visit(route("home"));
}
</script>
```

### Internal Structure

```
┌───────────────────────────────────────────────────────────────┐
│  Select Register                                    [✕ Close] │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  Store: Main Store                                           │
│                                                               │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │ ○  REG-01 (Main Register)                    Available  │  │
│  │    Status: Active | Default: Yes                        │  │
│  ├─────────────────────────────────────────────────────────┤  │
│  │ ○  REG-02 (Secondary)                  In Use by John D │  │
│  │    Status: Active | Cannot select                       │  │
│  ├─────────────────────────────────────────────────────────┤  │
│  │ ○  REG-03 (Backup)                         Inactive    │  │
│  │    Status: Inactive | Cannot select                     │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │  Opening Balance (for new shifts)                       │  │
│  │  $ [ 0.00 ]                                             │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                               │
├───────────────────────────────────────────────────────────────┤
│  [Cancel]                          [Select & Continue]        │
└───────────────────────────────────────────────────────────────┘
```

### States

#### Loading State
```vue
<Dialog :visible="true" :header="t('Select Register')">
  <div class="flex items-center justify-center py-8">
    <ProgressSpinner />
    <span class="ml-2">{{ t('Loading registers...') }}</span>
  </div>
</Dialog>
```

#### Empty State (No Registers)
```vue
<Dialog :visible="true" :header="t('Select Register')">
  <div class="text-center py-8">
    <i class="fa fa-exclamation-triangle text-4xl text-yellow-500 mb-4" aria-hidden="true" />
    <h3 class="text-lg font-semibold">{{ t('No registers available') }}</h3>
    <p class="text-surface-500 dark:text-surface-400">
      {{ t('Please contact your manager to set up a register.') }}
    </p>
  </div>
</Dialog>
```

#### Error State
```vue
<Dialog :visible="true" :header="t('Select Register')">
  <div class="text-center py-8">
    <i class="fa fa-times-circle text-4xl text-red-500 mb-4" aria-hidden="true" />
    <h3 class="text-lg font-semibold">{{ t('Failed to load registers') }}</h3>
    <p class="text-surface-500 dark:text-surface-400">{{ errorMessage }}</p>
    <Button :label="t('Retry')" @click="loadRegisters" />
  </div>
</Dialog>
```

---

## ShiftStatusBadge.vue

**Location:** `resources/js/Pages/Pos/Components/ShiftStatusBadge.vue`

### Purpose

Reusable badge component for displaying shift status with consistent styling.

### Props

```typescript
interface ShiftStatusBadgeProps {
  /** Shift status: 'open' | 'closed' */
  status: "open" | "closed";
  
  /** Optional: Show additional details */
  showDetails?: boolean;
  
  /** Shift number (optional) */
  shiftNumber?: string;
  
  /** Opening balance (optional) */
  openingBalance?: number;
}
```

### Example Usage

```vue
<template>
  <!-- Basic usage -->
  <ShiftStatusBadge status="open" />
  
  <!-- With details -->
  <ShiftStatusBadge 
    status="open" 
    :show-details="true"
    shift-number="00123"
    :opening-balance="500.00"
  />
</template>
```

### Rendered Output

```
Basic:  [ Open ]  (green badge)
        [ Closed ]  (gray badge)

With details:  [ Open ] Shift #00123 • Opened: Bs 500,00
```

> **Note:** Currency formatting uses `useCurrencyFormatter()` which respects the app's currency settings (BOB/Bs by default), not hardcoded USD.

---

## Composables

### usePosStore

**Location:** `resources/js/Composables/usePosStore.ts`

> **Prerequisite:** Pinia must be initialized in `resources/js/app.ts` with `app.use(createPinia())` before this store can be used.

```typescript
import { defineStore } from "pinia";
import { ref, computed } from "vue";
import type { Store, Register, Shift } from "@/Types/pos";

export const usePosStore = defineStore("pos", () => {
  // ========== State ==========
  const store = ref<Store | null>(null);
  const register = ref<Register | null>(null);
  const shift = ref<Shift | null>(null);
  const userId = ref<number | null>(null);
  
  // ========== Getters ==========
  const isShiftOpen = computed(() => shift.value?.status === "open");
  const isCashier = computed(() => shift.value?.cashier_id === userId.value);
  const hasRegister = computed(() => register.value !== null);
  const hasShift = computed(() => shift.value !== null);
  
  // ========== Actions ==========
  function setStore(data: Store): void {
    store.value = data;
  }
  
  function setRegister(data: Register): void {
    register.value = data;
  }
  
  function setShift(data: Shift | null): void {
    shift.value = data;
  }
  
  function setUserId(id: number): void {
    userId.value = id;
  }
  
  function clearSession(): void {
    store.value = null;
    register.value = null;
    shift.value = null;
  }
  
  return {
    // State
    store,
    register,
    shift,
    userId,
    // Getters
    isShiftOpen,
    isCashier,
    hasRegister,
    hasShift,
    // Actions
    setStore,
    setRegister,
    setShift,
    setUserId,
    clearSession,
  };
});
```

> **Note:** Shift open/close API calls are handled by `usePosClient`. The store holds reactive state; the composable handles network requests. This separation keeps the store testable and the API logic isolated. See `navigation.md` for the `usePosClient` implementation.

### usePosLayout

**Location:** `resources/js/Composables/usePosLayout.ts`

> **Simplified for 01a scope.** The shift bar collapse feature has been removed — the bar stays at 56px. Collapse can be added in a future iteration if needed.

```typescript
import { ref } from "vue";

// Module-level state (shared across all component instances, same pattern as useLayout)
const isShiftBarVisible = ref(true);

export function usePosLayout() {
  const shiftBarHeight = 56; // Fixed height in pixels

  function hideShiftBar(): void {
    isShiftBarVisible.value = false;
  }

  function showShiftBar(): void {
    isShiftBarVisible.value = true;
  }

  return {
    isShiftBarVisible,
    shiftBarHeight,
    hideShiftBar,
    showShiftBar,
  };
}
```

### usePosClient

**Location:** `resources/js/Composables/usePosClient.ts`

> **Full implementation with error handling is in `navigation.md`.** This composable wraps all POS API calls and handles network errors, permission errors, and session expiry.

Key methods:
- `getSession()` — GET current POS session state
- `getRegisters(storeId?)` — GET registers for selection dialog
- `selectRegister(registerId)` — POST select a register
- `openShift(registerId, openingBalance)` — POST open new shift
- `closeShift(shiftId, closingBalance?)` — POST close shift

All methods throw typed errors (`PosPermissionError`, `PosNetworkError`) that callers can catch and display appropriately.

---

## PrimeVue Components Used

| Component | Usage |
|-----------|-------|
| `Button` | Exit button, action buttons |
| `Badge` | Shift status indicator |
| `Dialog` | Register selection modal |
| `ConfirmDialog` | Shift close confirmation |
| `Toast` | Success/error notifications |
| `ProgressSpinner` | Loading state |
| `InputNumber` | Opening balance input |
| `RadioButton` | Register selection |

### Import Examples

```typescript
// Direct imports from primevue
import Button from "primevue/button";
import Badge from "primevue/badge";
import Dialog from "primevue/dialog";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import ProgressSpinner from "primevue/progressspinner";
import InputNumber from "primevue/inputnumber";
import RadioButton from "primevue/radiobutton";
```

> **Note:** `ConfirmDialog` must be included in `PosLayout.vue` to support the shift close confirmation. The `useConfirm()` composable provides the programmatic API.

---

## Implementation Checklist

### PosShiftBar
- [ ] Create component with fixed positioning
- [ ] Implement store, register, shift display
- [ ] Add "Exit POS" button
- [ ] Add "Close Shift" button (conditional, uses PrimeVue `useConfirm()`)
- [ ] Add `:aria-label` bindings (not plain `aria-label`) for all icon buttons
- [ ] Implement shift status badge
- [ ] Add slot for custom shift status
- [ ] Use `useCurrencyFormatter()` for opening balance
- [ ] Add `sr-only` labels next to decorative icons
- [ ] Test dark mode compatibility

### RegisterSelectDialog
- [ ] Create dialog component
- [ ] Implement register list rendering
- [ ] Add radio button selection
- [ ] Disable inactive registers
- [ ] Show "In Use by [Name]" for registers with other cashiers' shifts
- [ ] Handle "user already has open shift elsewhere" edge case
- [ ] Add opening balance input (VeeValidate + Yup validation)
- [ ] Implement loading state
- [ ] Implement empty state
- [ ] Implement error state with retry
- [ ] Add keyboard navigation (Enter to select, Escape to cancel)

### ShiftStatusBadge
- [ ] Create badge component
- [ ] Add status-based styling (open=green, closed=gray)
- [ ] Add optional details display
- [ ] Format currency using `useCurrencyFormatter()`

### Composables
- [ ] Initialize Pinia in `resources/js/app.ts`
- [ ] Create `usePosStore` Pinia store (state only — API calls in `usePosClient`)
- [ ] Create `usePosLayout` composable (simplified, no collapse)
- [ ] Create `usePosClient` composable with error handling
- [ ] Test state persistence across navigation