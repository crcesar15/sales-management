# POS Layout Implementation

## Layout Structure

```
┌─────────────────────────────────────────────────────────────────────┐
│ POS SHIFT BAR (fixed, 56px height, z-index: 1000)                  │
│ ┌───┬──────────────┬─────────────────┬──────────────────┬────────┐ │
│ │ ☰ │ Store: Main  │ Register: REG-01│ Shift #123 • $500│ [Exit] │ │
│ └───┴──────────────┴─────────────────┴──────────────────┴────────┘ │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  <slot /> — POS page content (full-width, scrolls independently)   │
│                                                                     │
│  ┌─────────────────────────┐  ┌─────────────────────────────────┐  │
│  │ Product Search          │  │ Cart Summary                    │  │
│  │ [Search/Barcode input]  │  │ Subtotal: $150.00               │  │
│  │                         │  │ Discount: -$15.00               │  │
│  │ Product Results/Cart    │  │ Tax (7%): $9.45                 │  │
│  │ ┌─────────────────────┐ │  │ Total: $144.45                  │  │
│  │ │ Item 1    Qty: 3    │ │  │                                 │  │
│  │ │ Item 2    Qty: 2    │ │  │ Payment Panel                   │  │
│  │ └─────────────────────┘ │  │ [Cash] [Card] [QR] [Split]      │  │
│  │                         │  │ [Checkout Button]               │  │
│  │ [Hold] [Clear]          │  │                                 │  │
│  └─────────────────────────┘  └─────────────────────────────────┘  │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

## Component Files

### PosLayout.vue

**Location:** `resources/js/Layouts/Components/PosLayout.vue`

```vue
<script setup lang="ts">
import { computed } from "vue";
import Toast from "primevue/toast";
import PosShiftBar from "./PosShiftBar.vue";
import { useLayout } from "./Composables/useLayout";

const { isDarkMode } = useLayout();

const containerClass = computed(() => ({
  "pos-layout": true,
  "pos-layout--dark": isDarkMode.value,
  "pos-layout--unsupported": isViewportUnsupported.value,
}));

// Viewport check (768px minimum)
const isViewportUnsupported = computed(() => {
  if (typeof window === "undefined") return false;
  return window.innerWidth < 768;
});
</script>

<template>
  <div :class="containerClass">
    <!-- Unsupported viewport message -->
    <div v-if="isViewportUnsupported" class="pos-unsupported-message">
      <i class="fa fa-tablet-alt" />
      <h2>POS requires a tablet or desktop</h2>
      <p>Please use a device with a screen width of at least 768px.</p>
    </div>

    <!-- Main POS interface -->
    <template v-else>
      <PosShiftBar />
      <main class="pos-main">
        <slot />
      </main>
    </template>

    <Toast position="top-center" :pt="{ root: { class: 'pos-toast-offset' } }" />
  </div>
</template>

<style lang="scss" scoped>
.pos-layout {
  min-height: 100vh;
  background-color: var(--surface-ground);
  color: var(--text-color);

  &--dark {
    background-color: var(--surface-ground-dark);
    color: var(--text-color-dark);
  }
}

.pos-main {
  padding-top: 56px; /* Shift bar height */
  height: 100vh;
  overflow-y: auto;
}

.pos-toast-offset {
  top: 64px !important; /* Shift bar height + 8px spacing */
}

.pos-unsupported-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  text-align: center;
  padding: 2rem;

  i {
    font-size: 4rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
  }

  h2 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }

  p {
    color: var(--text-muted-color);
  }
}
</style>
```

### PosShiftBar.vue

**Location:** `resources/js/Layouts/Components/PosShiftBar.vue`

```vue
<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import Button from "primevue/button";
import Badge from "primevue/badge";
import { usePosStore } from "@/Composables/usePosStore";

const { t } = useI18n();
const posStore = usePosStore();

const storeName = computed(() => posStore.store?.name ?? t("Store"));
const registerName = computed(() => posStore.register?.name ?? t("Register"));
const shiftStatus = computed(() => posStore.shift);
const isShiftOpen = computed(() => posStore.shift?.status === "open");
const isCashier = computed(() => posStore.shift?.cashier_id === posStore.userId);

const formattedOpeningBalance = computed(() => {
  if (!shiftStatus.value) return "$0.00";
  return formatCurrency(shiftStatus.value.opening_balance);
});

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
}

function exitPos(): void {
  router.visit(route("home"));
}

async function closeShift(): Promise<void> {
  if (!confirm(t("Are you sure you want to close this shift?"))) return;
  // Dispatch close shift action to posStore
}
</script>

<template>
  <header class="pos-shift-bar" role="banner" aria-label="Point of Sale navigation">
    <div class="pos-shift-bar__left">
      <!-- Exit button -->
      <Button
        v-tooltip.right="t('Exit POS')"
        icon="fa fa-bars"
        @click="exitPos"
        severity="secondary"
        text
        size="small"
        aria-label="t('Exit POS')"
      />

      <!-- Store name -->
      <span class="pos-shift-bar__info">
        <i class="fa fa-store" />
        {{ storeName }}
      </span>

      <!-- Register name -->
      <span class="pos-shift-bar__info">
        <i class="fa fa-cash-register" />
        {{ registerName }}
      </span>
    </div>

    <div class="pos-shift-bar__center">
      <!-- Shift status -->
      <div
        v-if="shiftStatus"
        class="pos-shift-bar__shift"
        aria-live="polite"
      >
        <Badge
          :label="isShiftOpen ? t('Open') : t('Closed')"
          :severity="isShiftOpen ? 'success' : 'secondary'"
        />
        <span class="pos-shift-bar__shift-details">
          {{ t("Shift") }} #{{ shiftStatus.shift_number }}
          <span class="pos-shift-bar__divider">•</span>
          {{ t("Opened") }}: {{ formattedOpeningBalance }}
        </span>
      </div>
      <div v-else class="pos-shift-bar__shift">
        <Badge :label="t('No shift')" severity="danger" />
      </div>
    </div>

    <div class="pos-shift-bar__right">
      <!-- Close shift button (only visible when shift is open and user is cashier) -->
      <Button
        v-if="isShiftOpen && isCashier"
        v-tooltip.left="t('Close shift')"
        icon="fa fa-lock"
        @click="closeShift"
        severity="danger"
        outlined
        size="small"
      />
    </div>
  </header>
</template>

<style lang="scss" scoped>
.pos-shift-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 56px;
  background-color: var(--surface-overlay);
  border-bottom: 1px solid var(--surface-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1rem;
  z-index: 1000;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

  &__left,
  &__center,
  &__right {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  &__info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-color);

    i {
      color: var(--primary-color);
    }
  }

  &__shift {
    display: flex;
    align-items: center;
    gap: 0.5rem;

    &-details {
      font-size: 0.875rem;
      color: var(--text-color-secondary);
    }

    &-divider {
      margin: 0 0.25rem;
      color: var(--surface-border);
    }
  }
}
</style>
```

## Design Tokens

### CSS Variables (Tailwind + PrimeVue)

| Token | Value | Usage |
|-------|-------|-------|
| `--pos-bar-height` | `56px` | Fixed header height |
| `--pos-bar-bg` | `var(--surface-overlay)` | Background color |
| `--pos-bar-border` | `var(--surface-border)` | Bottom border |
| `--pos-bar-text` | `var(--text-color)` | Primary text color |
| `--pos-bar-text-secondary` | `var(--text-color-secondary)` | Secondary text |
| `--pos-toast-offset` | `64px` | Toast position (bar height + 8px) |

### Tailwind Classes

| Element | Classes |
|---------|---------|
| Shift bar container | `fixed top-0 left-0 right-0 h-14 flex items-center justify-between px-4 z-[1000]` |
| Info section | `flex items-center gap-3 text-sm` |
| Badge | `text-xs px-2 py-0.5` |
| Button (exit) | `p-2 text-secondary hover:text-primary` |
| Main content area | `pt-14 h-screen overflow-y-auto` |

## Responsive Behavior

| Breakpoint | Behavior |
|------------|----------|
| `< 768px` | Show unsupported message, hide POS interface |
| `768px - 1023px` | Tablet layout, compact shift bar, stacked content panels |
| `≥ 1024px` | Desktop layout, full shift bar, side-by-side content panels |

### Tablet Layout (768px - 1023px)

```
┌─────────────────────────────────────────┐
│ SHIFT BAR (compact)                    │
├─────────────────────────────────────────┤
│ Product Search / Cart                  │
│ (full width)                           │
├─────────────────────────────────────────┤
│ Summary / Payment                      │
│ (full width, below cart)               │
└─────────────────────────────────────────┘
```

### Desktop Layout (≥ 1024px)

```
┌─────────────────────────────────────────────────────────┐
│ SHIFT BAR (full)                                       │
├──────────────────────────┬──────────────────────────────┤
│ Product Search / Cart    │ Summary / Payment            │
│ (left column, 60%)       │ (right column, 40%)          │
│                          │                              │
│ [Hold] [Clear]           │ Customer Select              │
│                          │ [Checkout]                   │
└──────────────────────────┴──────────────────────────────┘
```

## Layout Transitions

### Enter POS
```scss
.pos-main {
  animation: posFadeIn 200ms ease-out;
}

@keyframes posFadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

### Shift Bar Mount
```scss
.pos-shift-bar {
  animation: slideDown 150ms ease-out;
}

@keyframes slideDown {
  from {
    transform: translateY(-100%);
  }
  to {
    transform: translateY(0);
  }
}
```

### Exit POS
```typescript
function exitPos(): void {
  // Fade out effect before navigation
  document.querySelector(".pos-layout")?.classList.add("pos-exiting");
  setTimeout(() => {
    router.visit(route("home"));
  }, 200);
}
```

## Dark Mode Support

The POS layout inherits dark mode state from the app-wide `useLayout()` composable:

```typescript
const { isDarkMode } = useLayout();
```

### Dark Mode Styling

| Element | Light Mode | Dark Mode |
|---------|------------|-----------|
| Background | `var(--surface-ground)` | `var(--surface-ground-dark)` |
| Shift bar bg | `var(--surface-overlay)` | `var(--surface-overlay-dark)` |
| Text color | `var(--text-color)` | `var(--text-color-dark)` |
| Border | `var(--surface-border)` | `var(--surface-border-dark)` |

## PrimeVue Integration

### Toast Positioning

Toast notifications are positioned below the shift bar:

```vue
<Toast position="top-center" :pt="{ root: { class: 'pos-toast-offset' } }" />
```

```scss
.pos-toast-offset {
  top: 64px !important; /* 56px bar + 8px spacing */
}
```

### Dialog Integration

Register selection dialog uses PrimeVue Dialog:

```vue
<Dialog
  v-model:visible="dialogVisible"
  :header="t('Select Register')"
  :modal="true"
  :close-on-escape="true"
  :dismissable-mask="true"
>
  <!-- Register list -->
</Dialog>
```

## Pinia Store: usePosStore

**Location:** `resources/js/Composables/usePosStore.ts`

```typescript
import { defineStore } from "pinia";
import { ref, computed } from "vue";

interface Store {
  id: number;
  name: string;
}

interface Register {
  id: number;
  name: string;
  code: string;
  is_default: boolean;
  status: "active" | "inactive";
}

interface Shift {
  id: number;
  shift_number: string;
  register_id: number;
  cashier_id: number;
  opening_balance: number;
  status: "open" | "closed";
  opened_at: string;
}

export const usePosStore = defineStore("pos", () => {
  // State
  const store = ref<Store | null>(null);
  const register = ref<Register | null>(null);
  const shift = ref<Shift | null>(null);
  const userId = ref<number | null>(null);

  // Getters
  const isShiftOpen = computed(() => shift.value?.status === "open");
  const isCashier = computed(() => shift.value?.cashier_id === userId.value);

  // Actions
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

  async function openShift(registerId: number, openingBalance: number): Promise<void> {
    // API call to open shift
  }

  async function closeShift(shiftId: number): Promise<void> {
    // API call to close shift
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
    // Actions
    setStore,
    setRegister,
    setShift,
    setUserId,
    openShift,
    closeShift,
  };
});
```

## Implementation Checklist

- [ ] Create `PosLayout.vue` with full-screen structure
- [ ] Create `PosShiftBar.vue` with fixed positioning
- [ ] Implement viewport check (< 768px shows unsupported message)
- [ ] Add dark mode support via `useLayout()` composable
- [ ] Create `usePosStore` Pinia store
- [ ] Implement shift bar status display
- [ ] Add "Exit POS" button functionality
- [ ] Add "Close Shift" button (permission-gated)
- [ ] Position Toast below shift bar
- [ ] Add enter/exit transitions
- [ ] Test responsive behavior at 768px, 1024px
- [ ] Verify dark mode styling
