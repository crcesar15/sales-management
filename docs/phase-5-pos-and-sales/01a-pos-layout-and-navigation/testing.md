# POS Layout & Navigation Testing

## Test Strategy

This document defines the test cases for verifying the POS layout and navigation functionality.

---

## Backend Tests (Pest PHP)

### Feature Tests

**Location:** `tests/Feature/Pos/PosLayoutTest.php`

```php
<?php

use App\Models\User;
use App\Enums\PermissionsEnum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ========== ACCESS & AUTHORIZATION ==========

it('renders POS page for authorized user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);

    $response = $this->actingAs($user)->get(route('pos'));

    $response->assertOk();
    $response->assertInertia();
    $response->assertInertia(fn ($page) => 
        $page->component('Pos/Index')
             ->has('session.store')
             ->has('session.user')
    );
});

it('forbids POS access without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('pos'));

    $response->assertForbidden();
});

it('redirects guests to login', function () {
    $response = $this->get(route('pos'));

    $response->assertRedirect(route('login'));
});

// ========== SESSION MANAGEMENT ==========

it('returns POS session with store and user info', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);

    $response = $this->actingAs($user)->getJson(route('api.v1.pos.session'));

    $response->assertOk();
    $response->assertJsonStructure([
        'store' => ['id', 'name'],
        'user' => ['id', 'name', 'email'],
        'register',
        'shift',
    ]);
});

it('returns null register and shift when none assigned', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);

    $response = $this->actingAs($user)->getJson(route('api.v1.pos.session'));

    $response->assertOk();
    $response->assertJson([
        'register' => null,
        'shift' => null,
    ]);
});

// ========== REGISTER SELECTION ==========

it('lists registers for user store', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);
    $user->givePermissionTo(PermissionsEnum::CASH_REGISTER_VIEW->value);

    $store = Store::factory()->create();
    $user->stores()->attach($store);
    
    CashRegister::factory()->count(3)->create(['store_id' => $store->id]);

    $response = $this->actingAs($user)->getJson(route('api.v1.pos.registers'));

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
});

it('filters registers by store_id parameter', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([PermissionsEnum::POS_ACCESS->value, PermissionsEnum::CASH_REGISTER_VIEW->value]);

    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();
    $user->stores()->attach($store1);
    
    CashRegister::factory()->count(2)->create(['store_id' => $store1->id]);
    CashRegister::factory()->count(3)->create(['store_id' => $store2->id]);

    $response = $this->actingAs($user)->getJson(
        route('api.v1.pos.registers', ['store_id' => $store1->id])
    );

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

it('allows selecting a register', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);

    $register = CashRegister::factory()->create(['status' => 'active']);

    $response = $this->actingAs($user)->postJson(route('api.v1.pos.session.register'), [
        'register_id' => $register->id,
    ]);

    $response->assertOk();
    $response->assertJson([
        'register' => ['id' => $register->id],
    ]);
});

it('prevents selecting inactive register', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);

    $register = CashRegister::factory()->create(['status' => 'inactive']);

    $response = $this->actingAs($user)->postJson(route('api.v1.pos.session.register'), [
        'register_id' => $register->id,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('register_id');
});

// ========== SHIFT OPERATIONS ==========

it('allows opening a shift with opening balance', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([PermissionsEnum::POS_ACCESS->value, PermissionsEnum::SHIFT_OPEN->value]);

    $register = CashRegister::factory()->create(['status' => 'active']);

    $response = $this->actingAs($user)->postJson(route('api.v1.pos.session.shift.open'), [
        'register_id' => $register->id,
        'opening_balance' => 500.00,
    ]);

    $response->assertOk();
    $response->assertJsonPath('shift.status', 'open');
    $response->assertJsonPath('shift.opening_balance', 500.00);
    
    $this->assertDatabaseHas('cash_register_shifts', [
        'register_id' => $register->id,
        'cashier_id' => $user->id,
        'status' => 'open',
    ]);
});

it('prevents opening shift without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);

    $register = CashRegister::factory()->create();

    $response = $this->actingAs($user)->postJson(route('api.v1.pos.session.shift.open'), [
        'register_id' => $register->id,
        'opening_balance' => 500.00,
    ]);

    $response->assertForbidden();
});

it('prevents opening second shift on same register', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([PermissionsEnum::POS_ACCESS->value, PermissionsEnum::SHIFT_OPEN->value]);

    $register = CashRegister::factory()->create();
    CashRegisterShift::factory()->create([
        'register_id' => $register->id,
        'cashier_id' => $user->id,
        'status' => 'open',
    ]);

    $response = $this->actingAs($user)->postJson(route('api.v1.pos.session.shift.open'), [
        'register_id' => $register->id,
        'opening_balance' => 500.00,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('register_id');
});

it('allows closing own shift', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([PermissionsEnum::POS_ACCESS->value, PermissionsEnum::SHIFT_CLOSE->value]);

    $shift = CashRegisterShift::factory()->create([
        'cashier_id' => $user->id,
        'status' => 'open',
        'opening_balance' => 500.00,
    ]);

    $response = $this->actingAs($user)->postJson(route('api.v1.pos.session.shift.close'), [
        'shift_id' => $shift->id,
        'closing_balance' => 750.00,
    ]);

    $response->assertOk();
    $response->assertJsonPath('shift.status', 'closed');
    $response->assertJsonPath('shift.closing_balance', 750.00);
});

it('prevents closing another users shift without manage permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);

    $otherUser = User::factory()->create();
    $shift = CashRegisterShift::factory()->create([
        'cashier_id' => $otherUser->id,
        'status' => 'open',
    ]);

    $response = $this->actingAs($user)->postJson(route('api.v1.pos.session.shift.close'), [
        'shift_id' => $shift->id,
    ]);

    $response->assertForbidden();
});

// ========== ACTIVITY LOGGING ==========

it('logs shift open event', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([PermissionsEnum::POS_ACCESS->value, PermissionsEnum::SHIFT_OPEN->value]);

    $register = CashRegister::factory()->create();

    $this->actingAs($user)->postJson(route('api.v1.pos.session.shift.open'), [
        'register_id' => $register->id,
        'opening_balance' => 500.00,
    ]);

    Activity::assertLogged('shift_opened', $user)
        ->for($register)
        ->withProperties(['opening_balance' => 500.00]);
});

it('logs shift close event', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([PermissionsEnum::POS_ACCESS->value, PermissionsEnum::SHIFT_CLOSE->value]);

    $shift = CashRegisterShift::factory()->create([
        'cashier_id' => $user->id,
        'status' => 'open',
    ]);

    $this->actingAs($user)->postJson(route('api.v1.pos.session.shift.close'), [
        'shift_id' => $shift->id,
        'closing_balance' => 750.00,
    ]);

    Activity::assertLogged('shift_closed', $user)
        ->for($shift)
        ->withProperties([
            'closing_balance' => 750.00,
            'expected_closing_balance' => 500.00,
            'difference' => 250.00,
        ]);
});
```

---

## Frontend Tests (Pest with Vue Test Utils)

### Component Tests

**Location:** `tests/Javascript/Pages/Pos/PosLayout.test.js`

```javascript
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from '@/i18n';
import PosLayout from '@/Layouts/Components/PosLayout.vue';
import PosShiftBar from '@/Layouts/Components/PosShiftBar.vue';
import { usePosStore } from '@/Composables/usePosStore';

const i18n = createI18n();

describe('PosLayout', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('renders shift bar and main content area', () => {
    const wrapper = mount(PosLayout, {
      global: {
        plugins: [i18n],
        stubs: ['PosShiftBar', 'Toast'],
      },
      slots: {
        default: '<div data-testid="pos-content">POS Content</div>',
      },
    });

    expect(wrapper.findComponent(PosShiftBar).exists()).toBe(true);
    expect(wrapper.find('[data-testid="pos-content"]').text()).toBe('POS Content');
  });

  it('shows unsupported message on small viewports', async () => {
    vi.spyOn(window, 'innerWidth', 'get').mockReturnValue(600);

    const wrapper = mount(PosLayout, {
      global: {
        plugins: [i18n],
        stubs: ['PosShiftBar', 'Toast'],
      },
    });

    await wrapper.vm.$nextTick();

    expect(wrapper.find('.pos-unsupported-message').exists()).toBe(true);
    expect(wrapper.findComponent(PosShiftBar).exists()).toBe(false);
  });

  it('hides unsupported message on supported viewports', async () => {
    vi.spyOn(window, 'innerWidth', 'get').mockReturnValue(1024);

    const wrapper = mount(PosLayout, {
      global: {
        plugins: [i18n],
        stubs: ['PosShiftBar', 'Toast'],
      },
    });

    await wrapper.vm.$nextTick();

    expect(wrapper.find('.pos-unsupported-message').exists()).toBe(false);
    expect(wrapper.findComponent(PosShiftBar).exists()).toBe(true);
  });

  it('supports dark mode via useLayout composable', () => {
    const wrapper = mount(PosLayout, {
      global: {
        plugins: [i18n],
        stubs: ['PosShiftBar', 'Toast'],
      },
    });

    // Dark mode class should be applied based on useLayout state
    expect(wrapper.classes()).toContain('pos-layout');
  });
});
```

**Location:** `tests/Javascript/Pages/Pos/PosShiftBar.test.js`

```javascript
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from '@/i18n';
import { createRouter, createMemoryHistory } from 'vue-router';
import PosShiftBar from '@/Layouts/Components/PosShiftBar.vue';
import { usePosStore } from '@/Composables/usePosStore';

const i18n = createI18n();

describe('PosShiftBar', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  const mockProps = {
    store: { id: 1, name: 'Main Store' },
    register: { id: 1, name: 'REG-01', code: 'REG-01' },
    shift: {
      id: 1,
      shift_number: '00123',
      cashier_id: 1,
      cashier_name: 'John Doe',
      opening_balance: 500.00,
      status: 'open',
      opened_at: '2024-01-01T08:00:00Z',
    },
    userId: 1,
  };

  it('displays store name, register name, and shift info', () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: mockProps,
    });

    expect(wrapper.text()).toContain('Main Store');
    expect(wrapper.text()).toContain('REG-01');
    expect(wrapper.text()).toContain('00123');
    expect(wrapper.text()).toContain('$500.00');
  });

  it('shows open status badge when shift is open', () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: mockProps,
    });

    const badge = wrapper.findComponent({ name: 'Badge' });
    expect(badge.props('label')).toBe('Open');
    expect(badge.props('severity')).toBe('success');
  });

  it('shows closed status badge when shift is closed', () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: {
        ...mockProps,
        shift: { ...mockProps.shift, status: 'closed' },
      },
    });

    const badge = wrapper.findComponent({ name: 'Badge' });
    expect(badge.props('label')).toBe('Closed');
    expect(badge.props('severity')).toBe('secondary');
  });

  it('shows no shift message when shift is null', () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: {
        ...mockProps,
        shift: null,
      },
    });

    expect(wrapper.text()).toContain('No shift');
  });

  it('emits exit event when Exit button clicked', async () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: mockProps,
    });

    await wrapper.find('[aria-label="Exit POS"]').trigger('click');

    expect(wrapper.emitted('exit')).toHaveLength(1);
  });

  it('shows Close Shift button only when shift is open and user is cashier', async () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: mockProps,
    });

    // User is cashier (cashier_id === userId) and shift is open
    expect(wrapper.find('[aria-label="Close shift"]').exists()).toBe(true);

    // User is not cashier
    await wrapper.setProps({ ...mockProps, userId: 999 });
    expect(wrapper.find('[aria-label="Close shift"]').exists()).toBe(false);

    // Shift is closed
    await wrapper.setProps({ 
      ...mockProps, 
      userId: 1, 
      shift: { ...mockProps.shift, status: 'closed' } 
    });
    expect(wrapper.find('[aria-label="Close shift"]').exists()).toBe(false);
  });

  it('emits close-shift event when Close Shift button clicked', async () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: mockProps,
    });

    await wrapper.find('[aria-label="Close shift"]').trigger('click');

    expect(wrapper.emitted('close-shift')).toHaveLength(1);
  });

  it('has aria-live region for shift status', () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: mockProps,
    });

    const shiftStatus = wrapper.find('[aria-live="polite"]');
    expect(shiftStatus.exists()).toBe(true);
  });
});
```

**Location:** `tests/Javascript/Pages/Pos/RegisterSelectDialog.test.js`

```javascript
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from '@/i18n';
import RegisterSelectDialog from '@/Pages/Pos/Components/RegisterSelectDialog.vue';

const i18n = createI18n();

const mockRegisters = [
  { id: 1, name: 'REG-01', code: 'REG-01', status: 'active', is_default: true },
  { id: 2, name: 'REG-02', code: 'REG-02', status: 'active', is_default: false },
  { id: 3, name: 'REG-03', code: 'REG-03', status: 'inactive', is_default: false },
];

describe('RegisterSelectDialog', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('renders register list', () => {
    const wrapper = mount(RegisterSelectDialog, {
      global: {
        plugins: [i18n],
        stubs: ['Dialog', 'Button', 'RadioButton', 'InputNumber'],
      },
      props: {
        visible: true,
        registers: mockRegisters,
        storeName: 'Main Store',
      },
    });

    expect(wrapper.text()).toContain('Main Store');
    expect(wrapper.text()).toContain('REG-01');
    expect(wrapper.text()).toContain('REG-02');
    expect(wrapper.text()).toContain('REG-03');
  });

  it('disables inactive registers', () => {
    const wrapper = mount(RegisterSelectDialog, {
      global: {
        plugins: [i18n],
        stubs: ['Dialog', 'Button', 'RadioButton', 'InputNumber'],
      },
      props: {
        visible: true,
        registers: mockRegisters,
      },
    });

    const inactiveRadio = wrapper.find('[data-testid="register-3-radio"]');
    expect(inactiveRadio.props('disabled')).toBe(true);
  });

  it('emits select event when register is chosen', async () => {
    const wrapper = mount(RegisterSelectDialog, {
      global: {
        plugins: [i18n],
        stubs: ['Dialog', 'Button', 'RadioButton', 'InputNumber'],
      },
      props: {
        visible: true,
        registers: mockRegisters,
      },
    });

    // Select first register
    await wrapper.find('[data-testid="register-1-radio"]').trigger('click');
    await wrapper.find('[data-testid="select-button"]').trigger('click');

    expect(wrapper.emitted('select')).toHaveLength(1);
    expect(wrapper.emitted('select')[0]).toEqual([{ registerId: 1 }]);
  });

  it('shows loading state', () => {
    const wrapper = mount(RegisterSelectDialog, {
      global: {
        plugins: [i18n],
        stubs: ['Dialog', 'Button', 'RadioButton', 'InputNumber', 'ProgressSpinner'],
      },
      props: {
        visible: true,
        registers: [],
        loading: true,
      },
    });

    expect(wrapper.findComponent({ name: 'ProgressSpinner' }).exists()).toBe(true);
    expect(wrapper.text()).toContain('Loading registers');
  });

  it('shows empty state when no registers', () => {
    const wrapper = mount(RegisterSelectDialog, {
      global: {
        plugins: [i18n],
        stubs: ['Dialog', 'Button', 'RadioButton', 'InputNumber'],
      },
      props: {
        visible: true,
        registers: [],
      },
    });

    expect(wrapper.text()).toContain('No registers available');
  });

  it('emits cancel event when Cancel button clicked', async () => {
    const wrapper = mount(RegisterSelectDialog, {
      global: {
        plugins: [i18n],
        stubs: ['Dialog', 'Button', 'RadioButton', 'InputNumber'],
      },
      props: {
        visible: true,
        registers: mockRegisters,
      },
    });

    await wrapper.find('[data-testid="cancel-button"]').trigger('click');

    expect(wrapper.emitted('cancel')).toHaveLength(1);
  });

  it('emits open-shift event when selecting register without open shift', async () => {
    const wrapper = mount(RegisterSelectDialog, {
      global: {
        plugins: [i18n],
        stubs: ['Dialog', 'Button', 'RadioButton', 'InputNumber'],
      },
      props: {
        visible: true,
        registers: mockRegisters,
        selectedRegisterId: 1,
      },
    });

    // Enter opening balance and confirm
    await wrapper.find('[data-testid="opening-balance-input"]').setValue('500');
    await wrapper.find('[data-testid="open-shift-button"]').trigger('click');

    expect(wrapper.emitted('open-shift')).toHaveLength(1);
    expect(wrapper.emitted('open-shift')[0]).toEqual([{ registerId: 1, openingBalance: 500 }]);
  });
});
```

---

## Accessibility Tests

**Location:** `tests/Javascript/Accessibility/PosLayout.test.js`

```javascript
import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from '@/i18n';
import PosLayout from '@/Layouts/Components/PosLayout.vue';
import PosShiftBar from '@/Layouts/Components/PosShiftBar.vue';

const i18n = createI18n();

describe('POS Accessibility', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('has skip link for keyboard users', () => {
    const wrapper = mount(PosLayout, {
      global: {
        plugins: [i18n],
        stubs: ['PosShiftBar', 'Toast'],
      },
    });

    const skipLink = wrapper.find('.skip-link');
    expect(skipLink.exists()).toBe(true);
    expect(skipLink.attributes('href')).toBe('#pos-main');
  });

  it('has proper landmark roles', () => {
    const wrapper = mount(PosLayout, {
      global: {
        plugins: [i18n],
        stubs: ['PosShiftBar', 'Toast'],
      },
    });

    // Shift bar should have banner role
    const shiftBar = wrapper.find('[role="banner"]');
    expect(shiftBar.exists()).toBe(true);

    // Main content should have main role
    const main = wrapper.find('[role="main"]');
    expect(main.exists()).toBe(true);
  });

  it('has visible focus indicators', () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: {
        store: { id: 1, name: 'Main Store' },
        shift: null,
      },
    });

    // Check that focus styles are defined
    const styles = wrapper.find('button').element.style;
    // Focus indicator should be visible (check CSS or computed styles)
    expect(wrapper.find('button').classes()).toContain('focus:ring');
  });

  it('has aria-labels on icon buttons', () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: {
        store: { id: 1, name: 'Main Store' },
        shift: null,
      },
    });

    const exitButton = wrapper.find('[aria-label]');
    expect(exitButton.exists()).toBe(true);
    expect(exitButton.attributes('aria-label')).toContain('Exit');
  });

  it('has logical tab order', async () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: {
        store: { id: 1, name: 'Main Store' },
        shift: null,
      },
    });

    const focusableElements = wrapper.findAll('button, [tabindex="0"]');
    
    // First element should be Exit button
    expect(focusableElements[0].attributes('aria-label')).toContain('Exit');
  });

  it('announces shift status changes to screen readers', () => {
    const wrapper = mount(PosShiftBar, {
      global: {
        plugins: [i18n],
        stubs: ['Button', 'Badge'],
      },
      props: {
        store: { id: 1, name: 'Main Store' },
        shift: {
          id: 1,
          shift_number: '00123',
          status: 'open',
          opening_balance: 500,
        },
        userId: 1,
      },
    });

    // aria-live region should exist
    const liveRegion = wrapper.find('[aria-live="polite"]');
    expect(liveRegion.exists()).toBe(true);
  });
});

// Contrast ratio tests (requires axe-core or similar)
describe('Color Contrast', () => {
  it('meets WCAG AA contrast requirements', async () => {
    // This would use axe-core or similar tool
    // Example with axe-core:
    // const results = await axe.run(document.body);
    // expect(results.violations).toHaveLength(0);
  });
});
```

---

## E2E Tests (Playwright/Cypress)

**Location:** `tests/E2E/Pos/PosNavigation.spec.js`

```javascript
import { test, expect } from '@playwright/test';

test.describe('POS Navigation', () => {
  test.beforeEach(async ({ page }) => {
    // Login as user with POS access
    await page.goto('/login');
    await page.fill('[name="email"]', 'cashier@example.com');
    await page.fill('[name="password"]', 'password');
    await page.click('button[type="submit"]');
  });

  test('displays POS menu item for authorized user', async ({ page }) => {
    await page.goto('/');
    
    // Check that POS menu item exists in sidebar
    const posMenuItem = page.locator('text=Point of Sale');
    await expect(posMenuItem).toBeVisible();
  });

  test('hides POS menu item for unauthorized user', async ({ page }) => {
    // This would require logging in as a user without POS access
    // and verifying the menu item is not visible
  });

  test('navigates to POS page when clicking menu item', async ({ page }) => {
    await page.goto('/');
    
    await page.click('text=Point of Sale');
    
    // Should navigate to /pos
    await expect(page).toHaveURL('/pos');
    
    // Should render POS layout (check for shift bar)
    await expect(page.locator('.pos-shift-bar')).toBeVisible();
  });

  test('shows register selection when no open shift', async ({ page }) => {
    await page.goto('/pos');
    
    // Should show register selection dialog
    await expect(page.locator('text=Select Register')).toBeVisible();
    
    // Should list available registers
    await expect(page.locator('[data-testid="register-list"]')).toBeVisible();
  });

  test('allows selecting a register', async ({ page }) => {
    await page.goto('/pos');
    
    // Select first register
    await page.click('[data-testid="register-1-radio"]');
    await page.click('[data-testid="select-button"]');
    
    // Dialog should close
    await expect(page.locator('text=Select Register')).not.toBeVisible();
    
    // Shift bar should show selected register
    await expect(page.locator('.pos-shift-bar')).toContainText('REG-01');
  });

  test('allows opening a shift', async ({ page }) => {
    await page.goto('/pos');
    
    // Select register
    await page.click('[data-testid="register-1-radio"]');
    await page.click('[data-testid="select-button"]');
    
    // Enter opening balance
    await page.fill('[data-testid="opening-balance-input"]', '500');
    await page.click('[data-testid="open-shift-button"]');
    
    // Shift bar should show shift info
    await expect(page.locator('.pos-shift-bar')).toContainText('Open');
    await expect(page.locator('.pos-shift-bar')).toContainText('$500.00');
  });

  test('Exit POS button returns to dashboard', async ({ page }) => {
    await page.goto('/pos');
    
    // Click Exit POS
    await page.click('[aria-label="Exit POS"]');
    
    // Should navigate to home
    await expect(page).toHaveURL('/');
  });

  test('browser back button returns to admin', async ({ page }) => {
    await page.goto('/');
    const dashboardUrl = page.url();
    
    await page.goto('/pos');
    await page.goBack();
    
    await expect(page).toHaveURL(dashboardUrl);
  });

  test('supports keyboard navigation', async ({ page }) => {
    await page.goto('/pos');
    
    // Tab through elements
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    
    // Focus should move through interactive elements
    const focusedElement = page.locator(':focus');
    await expect(focusedElement).toBeVisible();
    
    // Escape should close dialogs
    await page.keyboard.press('Escape');
  });
});
```

---

## Test Execution Commands

```bash
# Run all POS tests
php artisan test --filter=Pos

# Run specific test file
php artisan test tests/Feature/Pos/PosLayoutTest.php

# Run JavaScript tests
npm run test:unit -- PosLayout

# Run accessibility tests
npm run test:a11y

# Run E2E tests
npx playwright test tests/E2E/Pos/
```

---

## Test Coverage Requirements

| Category | Minimum Coverage |
|----------|-----------------|
| Backend (PHP) | 80% |
| Frontend (JS) | 70% |
| Accessibility | 100% of WCAG criteria |
| E2E | Critical paths only |

### Critical Paths (Must Have E2E Tests)

1. User clicks "Point of Sale" → Register selection → Open shift → POS loads
2. Exit POS → Returns to dashboard
3. Close shift → Shift status updates
4. Keyboard navigation through entire POS flow
5. Screen reader announces shift status changes

---

## Manual Testing Checklist

### Layout & Navigation

- [ ] POS renders with PosLayout (no sidebar)
- [ ] Shift bar is fixed at top
- [ ] Shift bar shows correct store, register, shift info
- [ ] Exit POS button works
- [ ] Browser back button works
- [ ] Register selection dialog appears when needed
- [ ] Can select register and open shift

### Responsive

- [ ] Works at 768px width (tablet portrait)
- [ ] Works at 1024px width (desktop)
- [ ] Shows unsupported message below 768px
- [ ] Shift bar remains fixed on scroll

### Accessibility

- [ ] Can navigate entire flow with keyboard only
- [ ] Focus indicators are visible
- [ ] Screen reader announces shift status
- [ ] Skip link works
- [ ] All buttons have aria-labels
- [ ] Color contrast meets 4.5:1 ratio
- [ ] Works at 200% zoom

### Permissions

- [ ] User without `pos.access` cannot view POS
- [ ] User without `shift.open` cannot open shifts
- [ ] User without `shift.close` cannot close shifts
- [ ] Menu item hidden for unauthorized users
