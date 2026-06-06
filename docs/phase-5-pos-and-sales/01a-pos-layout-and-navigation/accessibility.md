# POS Accessibility Guide

## WCAG 2.1 AA Compliance

The POS interface must meet WCAG 2.1 Level AA standards to ensure usability for all cashiers, including those with disabilities.

---

## 1. Perceivable

### 1.1 Non-text Content

**Requirement:** All images, icons, and UI components must have text alternatives.

**Implementation:**

```vue
<!-- Icon buttons must have :aria-label binding (not plain aria-label) -->
<Button 
  icon="fa fa-bars" 
  :aria-label="t('Exit POS')"
  @click="exitPos"
  v-tooltip.right="t('Exit POS')"
/>

<!-- Product images need descriptive alt text -->
<img 
  :src="product.image" 
  :alt="t('Product image of {name}', { name: product.name })"
/>

<!-- Decorative icons should be hidden from screen readers -->
<i class="fa fa-store" aria-hidden="true" />
<span class="sr-only">{{ t('Store') }}:</span> {{ storeName }}
```

> **Important:** Use `:aria-label` (with colon binding) not `aria-label` (plain attribute). Without the binding, Vue treats it as a literal string, so `aria-label="t('Exit POS')"` would render the text `t('Exit POS')` literally instead of the translated string.

**Checklist:**
- [ ] All icon buttons have `:aria-label` (bound, not plain)
- [ ] Product images have descriptive `alt` text
- [ ] Decorative icons have `aria-hidden="true"`
- [ ] Status indicators have screen reader text

### 1.2 Time-based Media

**Requirement:** Not applicable to POS (no audio/video content).

### 1.3 Adaptable

**Requirement:** Content must be presentable in different layouts without losing information.

**Implementation:**

```vue
<!-- Use semantic HTML -->
<header class="pos-shift-bar" role="banner" :aria-label="t('Point of Sale navigation')">
  <h1 class="sr-only">{{ t('Point of Sale') }}</h1>
</header>

<main id="pos-main" class="pos-main" role="main">
  <slot />
</main>

<!-- Use proper heading hierarchy -->
<h2>{{ t('Products') }}</h2>
<h3>{{ t('Cart Items') }}</h3>

<!-- Tables need proper headers -->
<DataTable :value="cartItems">
  <Column field="name" :header="t('Product')" />
  <Column field="quantity" :header="t('Quantity')" />
  <Column field="total" :header="t('Total')" />
</DataTable>
```

**Checklist:**
- [ ] Semantic HTML elements used (`header`, `main`, `nav`)
- [ ] Proper heading hierarchy (h1 → h2 → h3)
- [ ] Tables have proper column headers
- [ ] Content reflows correctly at different zoom levels

### 1.4 Distinguishable

#### 1.4.1 Use of Color (Level A)

**Requirement:** Color must not be the only means of conveying information.

**Implementation:**

```vue
<!-- Status badges use color + text + icon -->
<Badge 
  :value="isShiftOpen ? t('Open') : t('Closed')"
  :severity="isShiftOpen ? 'success' : 'secondary'"
/>
<span class="sr-only">{{ isShiftOpen ? t('Shift is open') : t('Shift is closed') }}</span>
```

**Checklist:**
- [ ] Status indicators use icon + color + text
- [ ] Error messages have icons
- [ ] Selection states use more than color
- [ ] Links are underlined or have non-color indicators

#### 1.4.3 Contrast (Minimum) (Level AA)

**Requirement:** Text must have a contrast ratio of at least 4.5:1 for normal text, 3:1 for large text.

All POS components use Tailwind + PrimeVue design tokens which meet WCAG AA by default. Dark mode uses the `dark:` variant which also maintains contrast.

| Element | Light Mode | Dark Mode | Minimum Ratio |
|---------|------------|-----------|---------------|
| Primary text | `text-surface-900` on `bg-surface-0` | `dark:text-surface-0` on `dark:bg-surface-900` | 4.5:1 |
| Secondary text | `text-surface-700` on `bg-surface-0` | `dark:text-surface-300` on `dark:bg-surface-900` | 4.5:1 |
| Muted text | `text-surface-500` on `bg-surface-0` | `dark:text-surface-400` on `dark:bg-surface-900` | 4.5:1 |
| Badge text (success) | White on PrimeVue success green | Dark on PrimeVue success light | 4.5:1 |
| Disabled text | PrimeVue disabled state | PrimeVue disabled state dark | 3:1 |

**Checklist:**
- [ ] All text meets 4.5:1 contrast ratio in both modes
- [ ] Large text (18pt+) meets 3:1 contrast ratio
- [ ] Badge text is readable on all severity colors
- [ ] Disabled state text is still readable

#### 1.4.4 Resize Text (Level AA)

**Requirement:** Text must be resizable up to 200% without loss of content or functionality.

**Implementation:**

```html
<!-- Use Tailwind's rem-based font sizes -->
<p class="text-sm">{{ storeName }}</p>
<p class="text-base">{{ shiftDetails }}</p>

<!-- Ensure containers use min-height, not fixed height -->
<div class="min-h-[120px]">
  <!-- Product card content -->
</div>
```

**Checklist:**
- [ ] Font sizes use Tailwind rem-based classes (`text-sm`, `text-base`, etc.)
- [ ] Containers use `min-h-` instead of fixed `h-`
- [ ] Text doesn't clip at 200% zoom
- [ ] Buttons expand to accommodate larger text

#### 1.4.5 Images of Text (Level AA)

**Requirement:** Text must be actual text, not images of text.

**Implementation:**
- All text is rendered as DOM text nodes
- Icons are SVG or font-based (FontAwesome)
- No screenshots or images containing text

**Checklist:**
- [ ] No images containing essential text
- [ ] Logos use SVG or have text alternatives
- [ ] Icons are font-based or SVG

---

## 2. Operable

### 2.1 Keyboard Accessible

#### 2.1.1 Keyboard (Level A)

**Requirement:** All functionality must be available via keyboard.

**Implementation:**

```vue
<!-- All interactive elements are keyboard accessible by default with PrimeVue components -->
<Button 
  @click="exitPos"
  :aria-label="t('Exit POS')"
/>

<!-- Custom keyboard shortcuts for POS efficiency -->
<script setup lang="ts">
function handleKeydown(event: KeyboardEvent): void {
  // F2: Focus search
  if (event.key === "F2" && !isInputFocused.value) {
    event.preventDefault();
    searchInput.value?.focus();
  }
  
  // Escape: Close dialogs / Exit POS (with confirmation)
  if (event.key === "Escape") {
    if (dialogOpen.value) {
      dialogOpen.value = false;
    }
    // Escape on main POS screen does NOT exit — prevents accidental exits
    // Exit must be via explicit button click
  }
  
  // Enter: Submit forms
  if (event.key === "Enter" && event.target instanceof HTMLInputElement) {
    handleSubmit();
  }
}

onMounted(() => {
  window.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeydown);
});
</script>
```

> **Design decision:** The Escape key does NOT exit POS. Cashiers frequently press Escape to close dialogs, and accidentally exiting POS during a transaction would be disruptive. Exit must be via the explicit "Exit POS" button.

**Keyboard Navigation Map:**

| Key | Action |
|-----|--------|
| `Tab` | Move to next interactive element |
| `Shift + Tab` | Move to previous interactive element |
| `Enter` | Activate focused button/link, submit forms |
| `Space` | Activate focused button, toggle checkboxes |
| `Escape` | Close dialogs (NOT exit POS) |
| `F2` | Focus search bar |
| `F10` | Reserved for future: toggle shift bar collapse |
| `Arrow Up/Down` | Navigate lists, adjust quantities |
| `Arrow Left/Right` | Navigate tabs, payment methods |
| `Delete` | Remove item from cart (when focused) |

**Checklist:**
- [ ] All buttons are keyboard accessible
- [ ] All form inputs are keyboard accessible
- [ ] Dialogs trap focus and close with Escape
- [ ] Escape does NOT exit POS (only explicit button click exits)
- [ ] Custom keyboard shortcuts don't conflict with screen readers
- [ ] Focus is visible on all interactive elements

#### 2.1.2 No Keyboard Trap (Level A)

**Requirement:** Keyboard focus must not be trapped in any part of the content.

**Implementation:**

```vue
<!-- Dialog with proper focus management -->
<Dialog
  v-model:visible="isOpen"
  :modal="true"
  :close-on-escape="true"
  :dismissable-mask="true"
  @show="handleDialogOpen"
  @hide="handleDialogClose"
>
  <!-- Dialog content -->
</Dialog>

<script setup lang="ts">
const previousFocus = ref<HTMLElement | null>(null);

function handleDialogOpen(): void {
  previousFocus.value = document.activeElement as HTMLElement;
  // PrimeVue Dialog auto-focuses the first interactive element
}

function handleDialogClose(): void {
  // Restore focus to trigger element
  previousFocus.value?.focus();
}
</script>
```

**Checklist:**
- [ ] Dialogs can be closed with Escape
- [ ] Focus returns to trigger element after dialog closes
- [ ] No infinite tab loops
- [ ] Clicking outside dialog closes it (when appropriate)

#### 2.1.3 Character Key Shortcuts (Level A)

**Requirement:** If keyboard shortcuts use character keys, there must be a way to turn them off or remap them.

**Implementation:**
- Use function keys (F2, F10) instead of character keys — function keys don't conflict with screen readers or typing
- Document all shortcuts in a help dialog (future enhancement)
- No character key shortcuts are defined

**Checklist:**
- [ ] Function keys used instead of character keys
- [ ] Shortcuts documented in help dialog (future)
- [ ] Option to disable shortcuts (future enhancement)

### 2.2 Enough Time

#### 2.2.1 Timing Adjustable (Level A)

**Requirement:** Users must have enough time to read and interact with content.

**Implementation:**

```vue
<!-- Toast notifications with extended duration (5 seconds minimum) -->
<Toast 
  position="top-center" 
  :life="5000"
  :pt="{ root: { class: 'pos-toast-offset' } }"
/>

<!-- Critical errors don't auto-close — user must dismiss -->
<Toast 
  v-if="isCriticalError"
  :life="0"
/>
```

**Checklist:**
- [ ] Toast notifications last at least 5 seconds
- [ ] Critical errors don't auto-close
- [ ] No time limits on completing transactions
- [ ] Session timeout warning with option to extend (future)

### 2.3 Seizures and Physical Reactions

**Requirement:** Content must not flash more than three times per second.

**Implementation:**
- No animations that flash or blink
- Transitions are smooth fades (150-300ms)
- All transitions respect `prefers-reduced-motion`

```css
/* Safe transitions that respect reduced motion */
.pos-enter-active {
  @apply transition-all duration-200 ease-out;
}

@media (prefers-reduced-motion: reduce) {
  .pos-enter-active,
  .pos-leave-active {
    @apply transition-none;
  }
}
```

**Checklist:**
- [ ] No content flashes more than 3 times/second
- [ ] Animations are smooth transitions
- [ ] `prefers-reduced-motion` is respected

### 2.4 Navigable

#### 2.4.1 Bypass Blocks (Level A)

**Requirement:** Users must be able to bypass repeated content blocks.

**Implementation:**

```vue
<!-- Skip link for keyboard users (in PosLayout.vue) -->
<a href="#pos-main" class="skip-link sr-only focus:not-sr-only">
  {{ t('Skip to main content') }}
</a>

<header class="pos-shift-bar" role="banner" :aria-label="t('Point of Sale navigation')">
  <!-- Shift bar content -->
</header>

<main id="pos-main" class="pos-main" role="main">
  <slot />
</main>
```

```css
.skip-link {
  @apply absolute -top-10 left-0 z-[9999] px-4 py-2 bg-primary-500 text-white no-underline;
}

.skip-link:focus {
  @apply top-0;
}
```

**Checklist:**
- [ ] Skip link to main content
- [ ] Skip link visible on focus
- [ ] Main content has proper `id` for skip link target (`id="pos-main"`)

#### 2.4.2 Page Titled (Level A)

**Requirement:** Pages must have descriptive titles.

**Implementation:**

```typescript
// In Pos/Index.vue
defineOptions({
  layout: PosLayout,
});

// Page title set via Inertia head
// In the controller: Inertia::render('Pos/Index')->title('Point of Sale')
```

**Checklist:**
- [ ] Page title reflects current view
- [ ] Title includes application name
- [ ] Title changes when navigating within POS

#### 2.4.3 Focus Order (Level A)

**Requirement:** Focus order must preserve meaning and operability.

**Implementation:**

```vue
<!-- Logical tab order: left to right, top to bottom -->
<header>
  <button>{{ t('Exit POS') }}</button>  <!-- First -->
</header>

<main>
  <section aria-label="Products">
    <input type="search" />   <!-- Second -->
    <!-- ... -->
  </section>
  
  <section aria-label="Cart">
    <!-- ... -->
  </section>
  
  <section aria-label="Payment">
    <button>{{ t('Checkout') }}</button>  <!-- Last: primary action -->
  </section>
</main>
```

**Checklist:**
- [ ] Tab order follows visual layout
- [ ] Primary action (Checkout) is last in tab order
- [ ] No elements with positive `tabindex`
- [ ] Dialog focus starts on first interactive element

### 2.5 Input Modalities

#### 2.5.3 Target Size (Level AAA, recommended for POS)

**Requirement:** Touch targets must be at least 44×44px.

**Implementation:**

```html
<!-- PrimeVue Button size="small" has adequate touch targets -->
<!-- For custom buttons, ensure minimum size -->
<button class="min-w-[44px] min-h-[44px] flex items-center justify-center">
  <i class="fa fa-bars" aria-hidden="true" />
</button>
```

**Checklist:**
- [ ] All buttons are at least 44×44px
- [ ] Icon buttons have extended hit areas
- [ ] Table row actions have adequate touch targets
- [ ] Spacing between targets is at least 8px

---

## 3. Understandable

### 3.1 Readable

#### 3.1.1 Language of Page (Level A)

**Requirement:** Page language must be set.

The project uses `vue-i18n` and sets the HTML `lang` attribute. This is handled at the app level.

**Checklist:**
- [ ] HTML `lang` attribute is set (already handled by app)
- [ ] Language changes update `lang` attribute

### 3.2 Predictable

#### 3.2.3 Consistent Navigation (Level AA)

**Requirement:** Navigation must be consistent across pages.

**Implementation:**
- Shift bar is consistent across all POS pages
- "Exit POS" button always in same location (left side of shift bar)
- Keyboard shortcuts are consistent

**Checklist:**
- [ ] Shift bar appears on all POS pages
- [ ] Exit button in consistent location
- [ ] Keyboard shortcuts work consistently

### 3.3 Input Assistance

#### 3.3.1 Error Identification (Level A)

**Requirement:** Input errors must be identified and described in text.

**Implementation:**

```vue
<!-- Using VeeValidate + Yup (project pattern) -->
<Field
  name="opening_balance"
  v-slot="{ field, errorMessage }"
>
  <InputNumber
    v-bind="field"
    :class="{ 'p-invalid': errorMessage }"
    :aria-invalid="errorMessage ? 'true' : 'false'"
    :aria-describedby="errorMessage ? 'opening-balance-error' : undefined"
  />
  <small v-if="errorMessage" id="opening-balance-error" class="p-error" role="alert">
    {{ errorMessage }}
  </small>
</Field>
```

**Checklist:**
- [ ] Invalid fields have `aria-invalid="true"`
- [ ] Error messages have `role="alert"`
- [ ] Error messages reference field with `aria-describedby`
- [ ] Error messages are in text (not just color)

#### 3.3.4 Error Prevention (Legal, Financial, Data) (Level AA)

**Requirement:** Forms that commit financial transactions must be reversible or confirmable.

**Implementation:**

```vue
<!-- Shift close uses PrimeVue ConfirmDialog (not native confirm()) -->
<ConfirmDialog />

<script setup lang="ts">
import { useConfirm } from "primevue/useconfirm";
const confirm = useConfirm();

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
```

> **Important:** Use PrimeVue's `useConfirm()` + `ConfirmDialog` instead of the browser's native `confirm()`. The native dialog is not styled, not accessible, and breaks the POS UX.

**Checklist:**
- [ ] Shift close requires confirmation (PrimeVue ConfirmDialog)
- [ ] Order confirmation before checkout (Task 03 scope)
- [ ] User can go back and modify before committing
- [ ] Transaction creates reversible record (void/refund capability — future)

---

## 4. Robust

### 4.1 Compatible

#### 4.1.2 Name, Role, Value (Level A)

**Requirement:** Components must have accessible names, roles, and values.

**Implementation:**

```vue
<!-- Shift bar has banner role and label -->
<header class="pos-shift-bar" role="banner" :aria-label="t('Point of Sale navigation')">
  <!-- ... -->
</header>

<!-- Status updates announced to screen readers -->
<div aria-live="polite" class="sr-only">
  {{ statusMessage }}
</div>

<!-- Interactive elements have proper labels -->
<Button :aria-label="t('Exit POS')" @click="exitPos" />
<Button :aria-label="t('Close shift')" @click="closeShift" />
```

**Checklist:**
- [ ] Custom components have `role` attributes
- [ ] Interactive elements have `:aria-label` (bound) or `aria-labelledby`
- [ ] State changes use `aria-live` regions
- [ ] Tabindex is 0 or -1 (never positive)

---

## POS-Specific Accessibility Considerations

### Viewport Resize Handling

The viewport check for unsupported screens must be reactive, not a one-time computation:

```typescript
// CORRECT: Reactive viewport check
const windowWidth = ref(window.innerWidth);
onMounted(() => window.addEventListener("resize", updateWidth));
onUnmounted(() => window.removeEventListener("resize", updateWidth));
const isViewportUnsupported = computed(() => windowWidth.value < 768);

// WRONG: Static computation that never updates
const isViewportUnsupported = computed(() => window.innerWidth < 768);
```

**Reason:** Tablet users may rotate their device between portrait and landscape. A portrait orientation may be < 768px (unsupported) while landscape is ≥ 768px (supported). The UI must react to this change.

### Session Expiry Accessibility

When a POS session expires (401 response), the UI must:
1. Announce the session expiry via `aria-live="assertive"` region
2. Provide a clear path to re-authenticate
3. Not trap keyboard focus in an unusable state

```vue
<div aria-live="assertive" class="sr-only">
  {{ sessionExpiredMessage }}
</div>
```

### Error Handling Accessibility

All API errors must be accessible:
- Network errors: Toast notification with `aria-live="polite"`
- Permission errors: Toast notification with appropriate severity
- Validation errors: Field-level errors with `role="alert"` and `aria-describedby`

---

## Testing Checklist

### Manual Testing

- [ ] Navigate entire POS using only keyboard
- [ ] Test with screen reader (NVDA on Windows, VoiceOver on Mac)
- [ ] Zoom to 200% and verify no content loss
- [ ] Verify all color contrast ratios (light and dark mode)
- [ ] Test with high contrast mode enabled
- [ ] Verify focus indicators are visible
- [ ] Test dialog focus trapping and restoration
- [ ] Verify skip link functionality
- [ ] Test viewport resize (rotate tablet, resize browser window)
- [ ] Verify Escape key does NOT exit POS (only explicit button click)
- [ ] Test that `:aria-label` bindings render translated text (not literal `t('...')`)

### Automated Testing

- [ ] Run axe-core accessibility audit
- [ ] Run Lighthouse accessibility audit
- [ ] Check for missing alt attributes
- [ ] Check for missing form labels
- [ ] Verify ARIA attributes are valid
- [ ] Verify all `aria-label` uses `:` binding (not plain attribute)

### User Testing

- [ ] Test with actual screen reader users
- [ ] Test with users who have motor impairments
- [ ] Test with users who have low vision
- [ ] Gather feedback on keyboard navigation