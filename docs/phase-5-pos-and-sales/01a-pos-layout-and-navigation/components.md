# POS Components

## Reusable Components

This document defines the shared components used by the POS layout and interface.

---

## PosShiftBar.vue

**Location:** `resources/js/Layouts/Components/PosShiftBar.vue`

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
    status: 'open' | 'closed';
    opened_at: string;
  } | null;
  
  /** Current user ID (to check if user is the cashier) */
  userId?: number | null;
}
```

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

### Example Usage

```vue
<template>
  <PosShiftBar
    :store="posStore.store"
    :register="posStore.register"
    :shift="posStore.shift"
    :user-id="auth.user.id"
    @exit="exitPos"
    @close-shift="openCloseShiftDialog"
  />
</template>
```

### Internal Structure

```
┌─────────────────────────────────────────────────────────────────┐
│  [☰ Exit]  [🏪 Store]  [📠 Register]  [Shift Info]  [Actions]  │
│  │          │           │              │            │           │
│  │          │           │              │            └─ Close    │
│  │          │           │              │               Shift    │
│  │          │           │              │                        │
│  │          │           │              └─ Badge + Details       │
│  │          │           │                                        │
│  │          │           └─ Register name/code                    │
│  │          │                                                    │
│  │          └─ Store name                                        │
│  │                                                               │
│  └─ Hamburger menu (exit)                                        │
└─────────────────────────────────────────────────────────────────┘
```

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

function handleRegisterSelect({ registerId }: { registerId: number }) {
  // Proceed with selection
}

function handleOpenShift({ registerId, openingBalance }: { registerId: number, openingBalance: number }) {
  // Open new shift
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
│  │ ◉  REG-02 (Secondary)                        In Use     │  │
│  │    Status: Active | Cashier: John D.                    │  │
│  ├─────────────────────────────────────────────────────────┤  │
│  │ ○  REG-03 (Backup)                         Inactive     │  │
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
<template>
  <Dialog visible :header="t('Select Register')">
    <div class="flex items-center justify-center py-8">
      <ProgressSpinner />
      <span class="ml-2">{{ t('Loading registers...') }}</span>
    </div>
  </Dialog>
</template>
```

#### Empty State (No Registers)
```vue
<template>
  <Dialog visible :header="t('Select Register')">
    <div class="text-center py-8">
      <i class="fa fa-exclamation-triangle text-4xl text-yellow-500 mb-4" />
      <h3>{{ t('No registers available') }}</h3>
      <p>{{ t('Please contact your manager to set up a register.') }}</p>
    </div>
  </Dialog>
</template>
```

#### Error State
```vue
<template>
  <Dialog visible :header="t('Select Register')">
    <div class="text-center py-8">
      <i class="fa fa-times-circle text-4xl text-red-500 mb-4" />
      <h3>{{ t('Failed to load registers') }}</h3>
      <p>{{ errorMessage }}</p>
      <Button :label="t('Retry')" @click="loadRegisters" />
    </div>
  </Dialog>
</template>
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
  status: 'open' | 'closed';
  
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

With details:  [ Open ] Shift #00123 • Opened: $500.00
```

---

## Composables

### usePosStore

**Location:** `resources/js/Composables/usePosStore.ts`

```typescript
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { CashRegister, CashRegisterShift, Store } from '@/Types/pos';

export const usePosStore = defineStore('pos', () => {
  // ========== State ==========
  const store = ref<Store | null>(null);
  const register = ref<CashRegister | null>(null);
  const shift = ref<CashRegisterShift | null>(null);
  const userId = ref<number | null>(null);
  
  // ========== Getters ==========
  const isShiftOpen = computed(() => shift.value?.status === 'open');
  const isCashier = computed(() => shift.value?.cashier_id === userId.value);
  const hasRegister = computed(() => register.value !== null);
  const hasShift = computed(() => shift.value !== null);
  
  // ========== Actions ==========
  function setStore(data: Store): void {
    store.value = data;
  }
  
  function setRegister(data: CashRegister): void {
    register.value = data;
  }
  
  function setShift(data: CashRegisterShift | null): void {
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

### usePosLayout

**Location:** `resources/js/Composables/usePosLayout.ts`

```typescript
import { ref, computed } from 'vue';

export function usePosLayout() {
  const isShiftBarVisible = ref(true);
  const isShiftBarCollapsed = ref(false);
  
  const shiftBarHeight = computed(() => {
    if (!isShiftBarVisible.value) return '0px';
    if (isShiftBarCollapsed.value) return '32px';
    return '56px';
  });
  
  function toggleShiftBar(): void {
    isShiftBarVisible.value = !isShiftBarVisible.value;
  }
  
  function collapseShiftBar(): void {
    isShiftBarCollapsed.value = true;
  }
  
  function expandShiftBar(): void {
    isShiftBarCollapsed.value = false;
  }
  
  return {
    isShiftBarVisible,
    isShiftBarCollapsed,
    shiftBarHeight,
    toggleShiftBar,
    collapseShiftBar,
    expandShiftBar,
  };
}
```

### usePosClient

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
  
  async function closeShift(
    shiftId: number,
    closingBalance?: number
  ): Promise<PosSession> {
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

---

## PrimeVue Components Used

| Component | Usage |
|-----------|-------|
| `Button` | Exit button, action buttons |
| `Badge` | Shift status indicator |
| `Dialog` | Register selection modal |
| `Toast` | Success/error notifications |
| `ProgressSpinner` | Loading state |
| `InputNumber` | Opening balance input |
| `RadioButton` | Register selection |
| `Tooltip` | Button labels on hover |

### Import Examples

```typescript
// Direct imports from primevue
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import ProgressSpinner from 'primevue/progressspinner';
import InputNumber from 'primevue/inputnumber';
import RadioButton from 'primevue/radiobutton';
```

---

## Implementation Checklist

### PosShiftBar
- [ ] Create component with fixed positioning
- [ ] Implement store, register, shift display
- [ ] Add "Exit POS" button
- [ ] Add "Close Shift" button (conditional)
- [ ] Add aria-labels for accessibility
- [ ] Implement shift status badge
- [ ] Add slot for custom shift status
- [ ] Test dark mode compatibility

### RegisterSelectDialog
- [ ] Create dialog component
- [ ] Implement register list rendering
- [ ] Add radio button selection
- [ ] Disable inactive registers
- [ ] Add opening balance input
- [ ] Implement loading state
- [ ] Implement empty state
- [ ] Implement error state
- [ ] Add keyboard navigation (Enter to select)

### ShiftStatusBadge
- [ ] Create badge component
- [ ] Add status-based styling (open=green, closed=gray)
- [ ] Add optional details display
- [ ] Format currency for opening balance

### Composables
- [ ] Create `usePosStore` Pinia store
- [ ] Create `usePosLayout` composable
- [ ] Create `usePosClient` API client
- [ ] Test state persistence across navigation
