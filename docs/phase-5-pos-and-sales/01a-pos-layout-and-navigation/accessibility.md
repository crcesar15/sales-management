# POS Accessibility Guide

## WCAG 2.1 AA Compliance

The POS interface must meet WCAG 2.1 Level AA standards to ensure usability for all cashiers, including those with disabilities.

---

## 1. Perceivable

### 1.1 Non-text Content

**Requirement:** All images, icons, and UI components must have text alternatives.

**Implementation:**

```vue
<!-- Icon buttons must have aria-label -->
<Button 
  icon="fa fa-bars" 
  @click="exitPos"
  aria-label="t('Exit POS')"
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

**Checklist:**
- [ ] All icon buttons have `aria-label`
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
<header class="pos-shift-bar" role="banner">
  <h1 class="sr-only">{{ t('Point of Sale') }}</h1>
</header>

<main class="pos-main" role="main">
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
  :label="isShiftOpen ? t('Open') : t('Closed')"
  :severity="isShiftOpen ? 'success' : 'secondary'"
/>
<span class="sr-only">{{ isShiftOpen ? t('Shift is open') : t('Shift is closed') }}</span>

<!-- Error states use icon + color + text -->
<div class="error-message" role="alert">
  <i class="fa fa-exclamation-circle" aria-hidden="true" />
  <span>{{ errorMessage }}</span>
</div>

<!-- Payment method selection -->
<div class="payment-method" :class="{ selected: isSelected }">
  <RadioButton v-model="selected" :value="method.id" />
  <i :class="method.icon" aria-hidden="true" />
  <span>{{ method.name }}</span>
  <span v-if="isSelected" class="sr-only">{{ t('Selected') }}</span>
</div>
```

**Checklist:**
- [ ] Status indicators use icon + color + text
- [ ] Error messages have icons
- [ ] Selection states use more than color
- [ ] Links are underlined or have non-color indicators

#### 1.4.3 Contrast (Minimum) (Level AA)

**Requirement:** Text must have a contrast ratio of at least 4.5:1 for normal text, 3:1 for large text (18pt+ or 14pt+ bold).

**Implementation:**

```scss
// Light mode
.pos-shift-bar {
  background-color: #ffffff; // var(--surface-overlay)
  color: #1f2937; // var(--text-color) - contrast ratio 12.6:1
  
  &__info {
    color: #4b5563; // var(--text-color-secondary) - contrast ratio 7.2:1
  }
}

// Dark mode
.app-dark .pos-shift-bar {
  background-color: #1f2937; // var(--surface-overlay-dark)
  color: #f9fafb; // var(--text-color-dark) - contrast ratio 12.1:1
  
  &__info {
    color: #9ca3af; // var(--text-color-secondary-dark) - contrast ratio 6.8:1
  }
}
```

**Contrast Ratio Reference:**

| Element | Light Mode | Dark Mode | Minimum Ratio |
|---------|------------|-----------|---------------|
| Primary text | `#1f2937` on `#ffffff` | `#f9fafb` on `#1f2937` | 4.5:1 |
| Secondary text | `#4b5563` on `#ffffff` | `#9ca3af` on `#1f2937` | 4.5:1 |
| Badge text | `#ffffff` on `#22c55e` | `#1f2937` on `#4ade80` | 4.5:1 |
| Button text | `#ffffff` on `#3b82f6` | `#1f2937` on `#60a5fa` | 4.5:1 |

**Checklist:**
- [ ] All text meets 4.5:1 contrast ratio
- [ ] Large text (18pt+) meets 3:1 contrast ratio
- [ ] Badge text is readable on all severity colors
- [ ] Disabled state text is still readable

#### 1.4.4 Resize Text (Level AA)

**Requirement:** Text must be resizable up to 200% without loss of content or functionality.

**Implementation:**

```scss
// Use relative units (rem, em) instead of fixed px
.pos-shift-bar {
  font-size: 0.875rem; // 14px base
  
  &__info {
    font-size: 1em; // Inherits from parent
  }
}

// Ensure containers expand with text
.pos-main {
  min-height: 100vh; // Expands with content
}

// Avoid fixed-height containers that could clip text
.product-card {
  height: auto; // Not height: 100px
  min-height: 120px;
}
```

**Checklist:**
- [ ] Font sizes use `rem` or `em` units
- [ ] Containers use `min-height` instead of `height`
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
<!-- All interactive elements are keyboard accessible -->
<Button 
  @click="exitPos"
  @keydown.enter="exitPos"
  @keydown.space="exitPos"
/>

<!-- Custom keyboard shortcuts -->
<script setup lang="ts">
function handleKeydown(event: KeyboardEvent): void {
  // F2: Focus search
  if (event.key === 'F2' && !isInputFocused.value) {
    event.preventDefault();
    searchInput.value?.focus();
  }
  
  // Escape: Close dialogs / Exit POS
  if (event.key === 'Escape') {
    if (dialogOpen.value) {
      dialogOpen.value = false;
    } else {
      confirmExit();
    }
  }
  
  // Enter: Submit forms
  if (event.key === 'Enter' && event.target instanceof HTMLInputElement) {
    handleSubmit();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>
```

**Keyboard Navigation Map:**

| Key | Action |
|-----|--------|
| `Tab` | Move to next interactive element |
| `Shift + Tab` | Move to previous interactive element |
| `Enter` | Activate focused button/link, submit forms |
| `Space` | Activate focused button, toggle checkboxes |
| `Escape` | Close dialogs, exit POS (with confirmation) |
| `F2` | Focus search bar |
| `Arrow Up/Down` | Navigate lists, adjust quantities |
| `Arrow Left/Right` | Navigate tabs, payment methods |
| `Delete` | Remove item from cart (when focused) |

**Checklist:**
- [ ] All buttons are keyboard accessible
- [ ] All form inputs are keyboard accessible
- [ ] Dialogs trap focus and close with Escape
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
  :focus-on-show="true"
  @hide="restoreFocus"
>
  <!-- Dialog content -->
  <Button @click="closeDialog" ref="closeButtonRef">
    {{ t('Close') }}
  </Button>
</Dialog>

<script setup lang="ts">
const closeButtonRef = ref(null);
const previousFocus = ref<HTMLElement | null>(null);

function openDialog(): void {
  previousFocus.value = document.activeElement as HTMLElement;
  isOpen.value = true;
}

function closeDialog(): void {
  isOpen.value = false;
}

function restoreFocus(): void {
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
- Use function keys (F2, F10) instead of character keys
- Provide settings to disable shortcuts
- Document all shortcuts in a help dialog

```vue
<template>
  <Button 
    icon="fa fa-keyboard" 
    :label="t('Keyboard Shortcuts')"
    @click="showShortcutsHelp = true"
  />
  
  <Dialog v-model:visible="showShortcutsHelp" :header="t('Keyboard Shortcuts')">
    <table>
      <tr><th>F2</th><td>Focus search bar</td></tr>
      <tr><th>F10</th><td>Toggle shift bar</td></tr>
      <tr><th>Escape</th><td>Close dialog / Exit POS</td></tr>
      <tr><th>Enter</th><td>Submit / Confirm</td></tr>
    </table>
  </Dialog>
</template>
```

**Checklist:**
- [ ] Function keys used instead of character keys
- [ ] Shortcuts documented in help dialog
- [ ] Option to disable shortcuts (future enhancement)

### 2.2 Enough Time

#### 2.2.1 Timing Adjustable (Level A)

**Requirement:** Users must have enough time to read and interact with content.

**Implementation:**

```vue
<!-- Toast notifications with extended duration -->
<Toast 
  position="top-center" 
  :life="5000" 
  :pt="{ root: { class: 'pos-toast-offset' } }"
/>

<!-- No auto-closing for critical messages -->
<Dialog 
  v-model:visible="showError"
  :modal="true"
  :closable="true"
>
  <p>{{ errorMessage }}</p>
  <Button :label="t('OK')" @click="showError = false" />
</Dialog>
```

**Checklist:**
- [ ] Toast notifications last at least 5 seconds
- [ ] Critical errors don't auto-close
- [ ] No time limits on completing transactions
- [ ] Session timeout warning with option to extend

#### 2.2.2 Pause, Stop, Hide (Level A)

**Requirement:** Moving, blinking, or scrolling content must be controllable by the user.

**Implementation:**
- No auto-scrolling content
- No blinking animations
- Loading spinners are decorative (not essential information)

**Checklist:**
- [ ] No auto-scrolling content
- [ ] No blinking or flashing content (seizure risk)
- [ ] Loading indicators are non-essential

### 2.3 Seizures and Physical Reactions

#### 2.3.1 Three Flashes or Below Threshold (Level A)

**Requirement:** Content must not flash more than three times per second.

**Implementation:**
- No animations that flash or blink
- Transitions are smooth fades (200-300ms)

```scss
// Safe transitions
.pos-enter {
  animation: fadeIn 200ms ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

// NO rapid flashing animations
</style>
```

**Checklist:**
- [ ] No content flashes more than 3 times/second
- [ ] Animations are smooth transitions
- [ ] No strobe effects

### 2.4 Navigable

#### 2.4.1 Bypass Blocks (Level A)

**Requirement:** Users must be able to bypass repeated content blocks.

**Implementation:**

```vue
<!-- Skip link for keyboard users -->
<a href="#pos-main" class="skip-link sr-only focus:not-sr-only">
  {{ t('Skip to main content') }}
</a>

<header class="pos-shift-bar" role="banner">
  <!-- Shift bar content -->
</header>

<main id="pos-main" class="pos-main" role="main">
  <slot />
</main>
```

```scss
.skip-link {
  position: absolute;
  top: -40px;
  left: 0;
  z-index: 9999;
  padding: 8px 16px;
  background: var(--primary-color);
  color: white;
  text-decoration: none;
  
  &:focus {
    top: 0;
  }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
  
  &:not(.focus\\:not-sr-only):focus {
    width: auto;
    height: auto;
    padding: 8px 16px;
    margin: 0;
    overflow: visible;
    clip: auto;
  }
}
```

**Checklist:**
- [ ] Skip link to main content
- [ ] Skip link visible on focus
- [ ] Main content has proper `id` for skip link target

#### 2.4.2 Page Titled (Level A)

**Requirement:** Pages must have descriptive titles.

**Implementation:**

```typescript
// In Pos/Index.vue
defineOptions({
  layout: PosLayout,
});

// Page title is set via Inertia
const props = defineProps<{
  pageTitle: string;
}>();

onMounted(() => {
  document.title = `${props.pageTitle} - ${appName}`;
});
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
  <button>Exit POS</button>  <!-- First -->
</header>

<main>
  <section aria-label="Products">
    <input type="search" />   <!-- Second -->
    <DataTable>              <!-- Third: table contents -->
      <!-- Items tabbled row by row -->
    </DataTable>
  </section>
  
  <section aria-label="Cart">
    <Button>Hold Order</Button>  <!-- After products -->
    <Button>Clear Cart</Button>
  </section>
  
  <section aria-label="Payment">
    <InputNumber v-model="amount" />  <!-- Payment inputs -->
    <Button>Checkout</Button>         <!-- Last: primary action -->
  </section>
</main>
```

**Checklist:**
- [ ] Tab order follows visual layout
- [ ] Primary action (Checkout) is last in tab order
- [ ] No elements with positive `tabindex`
- [ ] Dialog focus starts on first interactive element

#### 2.4.4 Link Purpose (In Context) (Level A)

**Requirement:** Link purpose must be clear from the link text or context.

**Implementation:**

```vue
<!-- Clear link/button labels -->
<Button :label="t('Exit POS')" />
<Button :label="t('Close Shift')" />
<Link :href="route('customers')" :label="t('View Customers')" />

<!-- Avoid ambiguous labels like "Click here" -->
```

**Checklist:**
- [ ] All buttons have descriptive labels
- [ ] No "Click here" links
- [ ] Icon-only buttons have `aria-label`

### 2.5 Input Modalities

#### 2.5.1 Pointer Gestures (Level A)

**Requirement:** Functions that use gestures must also be available via single pointer.

**Implementation:**
- All swipe actions have button alternatives
- Drag-and-drop has click-to-select alternative
- Pinch-to-zoom is not required (browser default)

**Checklist:**
- [ ] No gesture-only interactions
- [ ] All actions available via click/tap
- [ ] Touch targets are 44×44px minimum

#### 2.5.2 Pointer Cancellation (Level A)

**Requirement:** Users must be able to cancel pointer input before completing an action.

**Implementation:**

```vue
<!-- Down-event triggers on mouse-up, not mouse-down -->
<Button 
  @click="handleClick"  <!-- Click fires on mouse-up -->
  @mousedown="handleDown"  <!-- Optional: visual feedback only -->
/>

<!-- Confirmation for destructive actions -->
<Button 
  :label="t('Clear Cart')" 
  severity="danger"
  @click="confirmClear"
/>

<Dialog v-model:visible="showClearConfirm">
  <p>{{ t('Are you sure you want to clear the cart?') }}</p>
  <Button :label="t('Cancel')" @click="showClearConfirm = false" />
  <Button :label="t('Clear')" severity="danger" @click="clearCart" />
</Dialog>
```

**Checklist:**
- [ ] Click actions trigger on mouse-up
- [ ] Destructive actions have confirmation
- [ ] Users can move pointer away to cancel

#### 2.5.3 Target Size (Level AAA)

**Requirement:** Touch targets must be at least 44×44px (Level AAA, but recommended for POS).

**Implementation:**

```scss
.button {
  min-width: 44px;
  min-height: 44px;
  padding: 0.5rem 1rem;
}

.icon-button {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
}

// For smaller icons, extend hit area
.small-icon {
  font-size: 16px;
  
  &::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 44px;
    height: 44px;
  }
}
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

**Implementation:**

```html
<!-- In app.blade.php or root layout -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
```

```vue
<!-- For dynamic language changes -->
<script setup lang="ts">
const { locale } = useI18n();

watch(locale, (newLocale) => {
  document.documentElement.lang = newLocale;
});
</script>
```

**Checklist:**
- [ ] HTML `lang` attribute is set
- [ ] Language matches content language
- [ ] Language changes update `lang` attribute

#### 3.1.2 Language of Parts (Level AA)

**Requirement:** Language of content sections must be identifiable.

**Implementation:**
- Not typically needed for POS (single language per session)
- If multi-language content appears, use `lang` attribute:

```html
<span lang="es">{{ spanishText }}</span>
```

**Checklist:**
- [ ] Multi-language content has proper `lang` attributes

### 3.2 Predictable

#### 3.2.1 On Focus (Level A)

**Requirement:** Components must not change state unexpectedly on focus.

**Implementation:**
- Focus doesn't trigger actions
- Focus only provides visual indication

```vue
<!-- Focus provides visual feedback only -->
<InputText 
  :model-value="modelValue"
  @focus="onFocus"  <!-- Visual feedback only -->
  @blur="onBlur"
  @change="onChange"  <!-- State change on change, not focus -->
/>
```

**Checklist:**
- [ ] Focus doesn't submit forms
- [ ] Focus doesn't open dialogs
- [ ] Focus provides visual indication only

#### 3.2.2 On Input (Level A)

**Requirement:** Changing input settings must not cause unexpected context changes.

**Implementation:**

```vue
<!-- Changing input doesn't navigate away -->
<InputNumber 
  v-model="quantity"
  @update:model-value="updateQuantity"
/>

<!-- Selection changes don't auto-submit -->
<Dropdown 
  v-model="selectedMethod"
  :options="paymentMethods"
  @change="updateMethod"  <!-- Updates state, doesn't submit -->
/>

<!-- Explicit submit button for form submission -->
<Button :label="t('Checkout')" @click="submitOrder" />
```

**Checklist:**
- [ ] Input changes don't trigger navigation
- [ ] Selection changes don't auto-submit
- [ ] Forms have explicit submit buttons

#### 3.2.3 Consistent Navigation (Level AA)

**Requirement:** Navigation must be consistent across pages.

**Implementation:**
- Shift bar is consistent across all POS pages
- "Exit POS" button always in same location
- Keyboard shortcuts are consistent

**Checklist:**
- [ ] Shift bar appears on all POS pages
- [ ] Exit button in consistent location
- [ ] Keyboard shortcuts work consistently

#### 3.2.4 Consistent Identification (Level AA)

**Requirement:** Components with same function must be consistently identified.

**Implementation:**

```vue
<!-- "Exit POS" always uses same label and icon -->
<Button 
  icon="fa fa-bars" 
  :label="t('Exit POS')"
  v-tooltip.right="t('Exit POS')"
/>

<!-- "Close Shift" always uses lock icon -->
<Button 
  icon="fa fa-lock" 
  :label="t('Close Shift')"
  severity="danger"
/>
```

**Checklist:**
- [ ] Same function = same label/icon
- [ ] Icons are consistent across pages
- [ ] Color meanings are consistent

### 3.3 Input Assistance

#### 3.3.1 Error Identification (Level A)

**Requirement:** Input errors must be identified and described in text.

**Implementation:**

```vue
<Form>
  <label for="barcode">{{ t('Barcode') }}</label>
  <InputText 
    id="barcode"
    v-model="barcode"
    :class="{ 'p-invalid': errors.barcode }"
    aria-invalid="errors.barcode ? 'true' : 'false'"
    aria-describedby="errors.barcode ? 'barcode-error' : null"
  />
  <small v-if="errors.barcode" id="barcode-error" class="p-error" role="alert">
    {{ errors.barcode }}
  </small>
</Form>
```

**Checklist:**
- [ ] Invalid fields have `aria-invalid="true"`
- [ ] Error messages have `role="alert"`
- [ ] Error messages reference field with `aria-describedby`
- [ ] Error messages are in text (not just color)

#### 3.3.2 Labels or Instructions (Level A)

**Requirement:** Input fields must have labels or instructions.

**Implementation:**

```vue
<!-- Visible labels for all inputs -->
<div class="field">
  <label for="quantity">{{ t('Quantity') }}</label>
  <InputNumber id="quantity" v-model="quantity" />
</div>

<!-- Placeholder is NOT a substitute for label -->
<InputText 
  id="search"
  v-model="search"
  :placeholder="t('Search products...')"
/>
<label for="search" class="sr-only">{{ t('Search products') }}</label>
```

**Checklist:**
- [ ] All inputs have visible labels
- [ ] Placeholders are not used as labels
- [ ] Required fields are indicated
- [ ] Input purpose is clear from label

#### 3.3.3 Error Suggestion (Level AA)

**Requirement:** Error messages should suggest how to fix the error.

**Implementation:**

```vue
<!-- Helpful error messages -->
<small v-if="errors.email" class="p-error" role="alert">
  {{ t('Please enter a valid email address (e.g., john@example.com)') }}
</small>

<small v-if="errors.amount" class="p-error" role="alert">
  {{ t('Amount must be between $0.01 and $9999.99') }}
</small>
```

**Checklist:**
- [ ] Error messages suggest how to fix
- [ ] Examples provided where helpful
- [ ] Valid ranges specified

#### 3.3.4 Error Prevention (Legal, Financial, Data) (Level AA)

**Requirement:** Forms that commit financial transactions must be reversible or confirmable.

**Implementation:**

```vue
<!-- Confirmation before checkout -->
<Dialog 
  v-model:visible="showCheckoutConfirm"
  :header="t('Confirm Order')"
  :modal="true"
>
  <div class="order-summary">
    <h4>{{ t('Items') }}</h4>
    <ul>
      <li v-for="item in cartItems" :key="item.id">
        {{ item.name }} × {{ item.quantity }} = {{ formatCurrency(item.total) }}
      </li>
    </ul>
    <hr />
    <p><strong>{{ t('Total') }}: {{ formatCurrency(orderTotal) }}</strong></p>
  </div>
  
  <div class="flex gap-4 justify-end">
    <Button :label="t('Back')" severity="secondary" @click="showCheckoutConfirm = false" />
    <Button :label="t('Confirm Order')" @click="confirmCheckout" />
  </div>
</Dialog>
```

**Checklist:**
- [ ] Checkout requires confirmation
- [ ] Order summary shown before commit
- [ ] User can go back and modify order
- [ ] Transaction creates reversible record (void/refund capability)

---

## 4. Robust

### 4.1 Compatible

#### 4.1.1 Parsing (Level A)

**Requirement:** HTML must be well-formed with unique IDs.

**Implementation:**

```vue
<!-- Unique IDs for aria-describedby -->
<InputText 
  :id="`search-${uniqueId}`"
  :aria-describedby="`search-help-${uniqueId}`"
/>
<small :id="`search-help-${uniqueId}`">{{ t('Search by name or barcode') }}</small>

<script setup lang="ts">
const uniqueId = computed(() => `pos-${Math.random().toString(36).slice(2)}`);
</script>
```

**Checklist:**
- [ ] All IDs are unique within page
- [ ] HTML elements are properly nested
- [ ] No duplicate attributes

#### 4.1.2 Name, Role, Value (Level A)

**Requirement:** Components must have accessible names, roles, and values.

**Implementation:**

```vue
<!-- Custom components with proper ARIA -->
<div 
  class="custom-checkbox"
  role="checkbox"
  :aria-checked="isChecked"
  :aria-label="label"
  @click="toggle"
  @keydown.enter="toggle"
  @keydown.space="toggle"
  tabindex="0"
>
  <i v-if="isChecked" class="fa fa-check" aria-hidden="true" />
</div>

<!-- Status updates announced to screen readers -->
<div aria-live="polite" class="sr-only">
  {{ statusMessage }}
</div>
```

**Checklist:**
- [ ] Custom components have `role` attributes
- [ ] Interactive elements have `aria-label` or `aria-labelledby`
- [ ] State changes use `aria-live` regions
- [ ] Tabindex is 0 or -1 (never positive)

---

## Testing Checklist

### Manual Testing

- [ ] Navigate entire POS using only keyboard
- [ ] Test with screen reader (NVDA on Windows, VoiceOver on Mac)
- [ ] Zoom to 200% and verify no content loss
- [ ] Verify all color contrast ratios
- [ ] Test with high contrast mode enabled
- [ ] Verify focus indicators are visible
- [ ] Test dialog focus trapping
- [ ] Verify skip link functionality

### Automated Testing

- [ ] Run axe-core accessibility audit
- [ ] Run Lighthouse accessibility audit
- [ ] Check for missing alt attributes
- [ ] Check for missing form labels
- [ ] Verify ARIA attributes are valid

### User Testing

- [ ] Test with actual screen reader users
- [ ] Test with users who have motor impairments
- [ ] Test with users who have low vision
- [ ] Gather feedback on keyboard navigation
