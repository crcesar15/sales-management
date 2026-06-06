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
import { computed, ref, onMounted, onUnmounted } from "vue";
import Toast from "primevue/toast";
import PosShiftBar from "@/Layouts/Components/PosShiftBar.vue";
import { useLayout } from "@/Layouts/Components/Composables/useLayout";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const { isDarkMode } = useLayout();
const { t } = useI18n();

// Reactive viewport check (updates on resize)
const windowWidth = ref(window.innerWidth);

const updateWidth = () => {
  windowWidth.value = window.innerWidth;
};

onMounted(() => {
  window.addEventListener("resize", updateWidth);
});

onUnmounted(() => {
  window.removeEventListener("resize", updateWidth);
});

const isViewportUnsupported = computed(() => windowWidth.value < 768);

const containerClass = computed(() => [
  "pos-layout",
  { "pos-layout--dark": isDarkMode.value },
]);
</script>

<template>
  <!-- Skip link for keyboard users -->
  <a href="#pos-main" class="skip-link sr-only focus:not-sr-only">
    {{ t("Skip to main content") }}
  </a>

  <div :class="containerClass">
    <!-- Unsupported viewport message -->
    <div v-if="isViewportUnsupported" class="pos-unsupported-message">
      <i class="fa fa-tablet-alt text-6xl text-primary-500 mb-4" aria-hidden="true" />
      <h2 class="text-xl font-semibold mb-2">{{ t("POS requires a tablet or desktop") }}</h2>
      <p class="text-surface-500 dark:text-surface-400 mb-4">
        {{ t("Please use a device with a screen width of at least 768px.") }}
      </p>
      <a :href="route('home')" class="text-primary-500 underline">
        {{ t("Return to Dashboard") }}
      </a>
    </div>

    <!-- Main POS interface -->
    <template v-else>
      <PosShiftBar />
      <main id="pos-main" class="pos-main" role="main">
        <slot />
      </main>
    </template>

    <Toast position="top-center" :pt="{ root: { class: "pos-toast-offset" } }" />
  </div>
</template>

<style scoped>
.pos-layout {
  @apply min-h-screen bg-surface-ground text-surface-900;
}

.pos-layout--dark {
  @apply bg-surface-900 text-surface-0;
}

.pos-main {
  @apply pt-14 h-screen overflow-y-auto;
}

.pos-toast-offset {
  top: 64px !important; /* 56px bar + 8px spacing */
}

.pos-unsupported-message {
  @apply flex flex-col items-center justify-center h-screen text-center p-8;
}

/* Skip link for accessibility */
.skip-link {
  @apply absolute -top-10 left-0 z-[9999] px-4 py-2 bg-primary-500 text-white no-underline;
}

.skip-link:focus {
  @apply top-0;
}

.sr-only {
  @apply absolute w-px h-px p-0 -m-px overflow-hidden whitespace-nowrap border-0;
  clip: rect(0, 0, 0, 0);
}

.sr-only:focus {
  @apply w-auto h-auto p-2 m-0 overflow-visible whitespace-normal;
  clip: auto;
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
import { useConfirm } from "primevue/useconfirm";
import Button from "primevue/button";
import Badge from "primevue/badge";
import { usePosStore } from "@/Composables/usePosStore";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

const { t } = useI18n();
const confirm = useConfirm();
const posStore = usePosStore();
const { formatCurrency } = useCurrencyFormatter();

const storeName = computed(() => posStore.store?.name ?? t("Store"));
const registerName = computed(() => posStore.register?.name ?? t("Register"));
const shiftStatus = computed(() => posStore.shift);
const isShiftOpen = computed(() => posStore.shift?.status === "open");
const isCashier = computed(() => posStore.shift?.cashier_id === posStore.userId);

const formattedOpeningBalance = computed(() => {
  if (!shiftStatus.value) return formatCurrency("0");
  return formatCurrency(shiftStatus.value.opening_balance.toString());
});

function exitPos(): void {
  // Cart check will be added in Task 02 (POS Interface)
  router.visit(route("home"));
}

function closeShift(): void {
  confirm.require({
    message: t("Are you sure you want to close this shift?"),
    header: t("Close Shift"),
    icon: "fa fa-exclamation-triangle",
    acceptLabel: t("Yes, close shift"),
    rejectLabel: t("Cancel"),
    accept: () => {
      posStore.closeShift(posStore.shift!.id);
    },
  });
}
</script>

<template>
  <header class="pos-shift-bar" role="banner" :aria-label="t('Point of Sale navigation')">
    <h1 class="sr-only">{{ t("Point of Sale") }}</h1>

    <div class="pos-shift-bar__left">
      <!-- Exit button -->
      <Button
        v-tooltip.right="t('Exit POS')"
        icon="fa fa-bars"
        :aria-label="t('Exit POS')"
        @click="exitPos"
        severity="secondary"
        text
        size="small"
      />

      <!-- Store name -->
      <span class="pos-shift-bar__info">
        <i class="fa fa-store" aria-hidden="true" />
        <span class="sr-only">{{ t("Store") }}:</span>
        {{ storeName }}
      </span>

      <!-- Register name -->
      <span class="pos-shift-bar__info">
        <i class="fa fa-cash-register" aria-hidden="true" />
        <span class="sr-only">{{ t("Register") }}:</span>
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
          :value="isShiftOpen ? t('Open') : t('Closed')"
          :severity="isShiftOpen ? 'success' : 'secondary'"
        />
        <span class="pos-shift-bar__shift-details">
          {{ t("Shift") }} #{{ shiftStatus.shift_number }}
          <span class="pos-shift-bar__divider" aria-hidden="true">&bull;</span>
          {{ t("Opened") }}: {{ formattedOpeningBalance }}
        </span>
      </div>
      <div v-else class="pos-shift-bar__shift" aria-live="polite">
        <Badge :value="t('No shift')" severity="danger" />
      </div>
    </div>

    <div class="pos-shift-bar__right">
      <!-- Close shift button (only visible when shift is open and user is cashier) -->
      <Button
        v-if="isShiftOpen && isCashier"
        v-tooltip.left="t('Close shift')"
        icon="fa fa-lock"
        :aria-label="t('Close shift')"
        @click="closeShift"
        severity="danger"
        outlined
        size="small"
      />
    </div>
  </header>
</template>

<style scoped>
.pos-shift-bar {
  @apply fixed top-0 left-0 right-0 h-14 flex items-center justify-between px-4 z-[1000];
  @apply bg-surface-0 dark:bg-surface-900 border-b border-surface-200 dark:border-surface-700;
  @apply shadow-sm;
}

.pos-shift-bar__left,
.pos-shift-bar__center,
.pos-shift-bar__right {
  @apply flex items-center gap-4;
}

.pos-shift-bar__info {
  @apply flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300;
}

.pos-shift-bar__info i {
  @apply text-primary-500;
}

.pos-shift-bar__shift {
  @apply flex items-center gap-2;
}

.pos-shift-bar__shift-details {
  @apply text-sm text-surface-500 dark:text-surface-400;
}

.pos-shift-bar__divider {
  @apply mx-1 text-surface-300 dark:text-surface-600;
}

.sr-only {
  @apply absolute w-px h-px p-0 -m-px overflow-hidden whitespace-nowrap border-0;
  clip: rect(0, 0, 0, 0);
}
</style>
```

## Design Tokens

### Tailwind Classes (Primary Approach)

All styling uses Tailwind CSS utility classes with PrimeVue's design tokens (surface-*, primary-*, text-*). Dark mode is handled via Tailwind's `dark:` variant, which activates when the `app-dark` class is on `<html>` (managed by `useLayout()`).

| Element | Tailwind Classes |
|---------|---------|
| Shift bar container | `fixed top-0 left-0 right-0 h-14 flex items-center justify-between px-4 z-[1000] bg-surface-0 dark:bg-surface-900 border-b border-surface-200 dark:border-surface-700 shadow-sm` |
| Info section | `flex items-center gap-4 text-sm text-surface-700 dark:text-surface-300` |
| Badge | Inherited from PrimeVue Badge component |
| Button (exit) | PrimeVue Button `text` variant with `severity="secondary"` |
| Main content area | `pt-14 h-screen overflow-y-auto` |
| Unsupported message | `flex flex-col items-center justify-center h-screen text-center p-8` |

### z-index Scale

| Token | Value | Usage |
|-------|-------|-------|
| Shift bar | `z-[1000]` | Fixed header above all content |
| Toast | `z-[1001]` | Toast notifications above shift bar |
| Dialog/Modal | PrimeVue default (1100+) | Register selection, confirmations |
| Skip link | `z-[9999]` | Accessibility skip link |

## Responsive Behavior

| Breakpoint | Behavior |
|------------|----------|
| `< 768px` | Show unsupported message with dashboard link, hide POS interface |
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
```css
.pos-enter-active {
  @apply transition-all duration-200 ease-out;
}
.pos-enter-from {
  @apply opacity-0 translate-y-2;
}
```

### Shift Bar Mount
```css
.shift-bar-enter-active {
  @apply transition-transform duration-150 ease-out;
}
.shift-bar-enter-from {
  @apply -translate-y-full;
}
```

> **Note:** All transitions respect `prefers-reduced-motion`. See accessibility.md for implementation details.

## Dark Mode Support

The POS layout inherits dark mode state from the app-wide `useLayout()` composable:

```typescript
import { useLayout } from "@/Layouts/Components/Composables/useLayout";
const { isDarkMode } = useLayout();
```

The `useLayout()` composable toggles the `app-dark` CSS class on `<html>`, which PrimeVue uses as its `darkModeSelector`. All POS components use Tailwind's `dark:` variant for dark mode styling — no separate CSS variables needed.

### Dark Mode Styling

| Element | Light Mode | Dark Mode |
|---------|------------|-----------|
| Shift bar background | `bg-surface-0` | `dark:bg-surface-900` |
| Shift bar border | `border-surface-200` | `dark:border-surface-700` |
| Info text | `text-surface-700` | `dark:text-surface-300` |
| Secondary text | `text-surface-500` | `dark:text-surface-400` |
| Layout background | `bg-surface-ground` | `dark:bg-surface-900` |
| Main text | `text-surface-900` | `dark:text-surface-0` |

## PrimeVue Integration

### Toast Positioning

Toast notifications are positioned below the shift bar using a dedicated Toast instance in `PosLayout.vue`:

```vue
<Toast position="top-center" :pt="{ root: { class: 'pos-toast-offset' } }" />
```

```css
.pos-toast-offset {
  top: 64px !important; /* 56px bar + 8px spacing */
}
```

> **Important:** `PosLayout.vue` includes its own `<Toast>` component with the offset. Since POS replaces `AppLayout` entirely, there is no duplicate toast issue. Do NOT render `AppLayout`'s toast inside POS.

### Confirmation Dialog (Instead of Native `confirm()`)

Use PrimeVue's `useConfirm()` composable instead of the browser's native `confirm()` dialog. The POS layout includes a `<ConfirmDialog>` for shift close confirmations:

```vue
<ConfirmDialog />
```

```typescript
import { useConfirm } from "primevue/useconfirm";
const confirm = useConfirm();

confirm.require({
  message: t("Are you sure you want to close this shift?"),
  header: t("Close Shift"),
  icon: "fa fa-exclamation-triangle",
  acceptLabel: t("Yes, close shift"),
  rejectLabel: t("Cancel"),
  accept: () => { /* close shift */ },
});
```

## Pinia Store: usePosStore

**Location:** `resources/js/Composables/usePosStore.ts`

> **Prerequisite:** Pinia must be initialized in `resources/js/app.ts` with `app.use(createPinia())` before this store can be used.

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

  function clearSession(): void {
    store.value = null;
    register.value = null;
    shift.value = null;
  }

  // Shift operations delegate to usePosClient (defined in navigation.md)
  // openShift and closeShift are async actions that call the API
  // and update local state on success

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
    clearSession,
  };
});
```

> **Note:** Shift open/close API calls are handled by `usePosClient` composable (see navigation.md). The store holds the reactive state; the composable handles the network requests. This separation keeps the store testable and the API logic isolated.

## usePosLayout Composable

**Location:** `resources/js/Composables/usePosLayout.ts`

> **Note:** The shift bar collapse feature has been removed from 01a scope. The bar is always visible at 56px. This composable is simplified accordingly — collapse can be added in a future iteration if needed.

```typescript
import { ref } from "vue";

// Module-level state (shared across all component instances)
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

> **Why module-level `ref`?** Using module-level state (outside the function) ensures all components that call `usePosLayout()` share the same reactive state. This is the same pattern used by the project's existing `useLayout()` composable.

## Implementation Checklist

- [ ] Add `createPinia()` to `resources/js/app.ts`
- [ ] Create `PosLayout.vue` with full-screen structure
- [ ] Create `PosShiftBar.vue` with fixed positioning
- [ ] Implement reactive viewport check (< 768px shows unsupported message with dashboard link)
- [ ] Add dark mode support via Tailwind `dark:` variant (not custom CSS variables)
- [ ] Create `usePosStore` Pinia store
- [ ] Create `usePosLayout` composable (simplified, no collapse)
- [ ] Use `useCurrencyFormatter()` for currency display (not hardcoded formatter)
- [ ] Use `useConfirm()` (PrimeVue) instead of native `confirm()` for shift close
- [ ] Implement shift bar status display
- [ ] Add "Exit POS" button functionality
- [ ] Add "Close Shift" button (permission-gated)
- [ ] Position Toast below shift bar (dedicated Toast instance in PosLayout)
- [ ] Add skip link for accessibility
- [ ] Add enter/exit transitions (respect `prefers-reduced-motion`)
- [ ] Test responsive behavior at 768px, 1024px
- [ ] Verify dark mode styling
- [ ] Add all translation keys to `en.json` and `es.json`