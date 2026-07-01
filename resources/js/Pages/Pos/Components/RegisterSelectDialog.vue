<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number } from "yup";
import { Dialog, Button, RadioButton, InputNumber, ProgressSpinner } from "primevue";
import { usePosStore } from "@/Composables/usePosStore";
import { usePosClient } from "@/Composables/usePosClient";
import type { CashRegister } from "@/Types/pos";

interface RegisterStatus {
  state: "inactive" | "in-use" | "own-shift" | "available";
  selectable: boolean;
  label: string;
}

defineEmits<{
  (e: "cancel"): void;
}>();

const visible = defineModel<boolean>("visible", { default: false });

const { t } = useI18n();
const posStore = usePosStore();
const posClient = usePosClient();

const registers = ref<CashRegister[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const selectedRegisterId = ref<number | null>(null);
const showOpenShiftForm = ref(false);

const storeName = computed(() => posStore.store?.name ?? t("Store"));

// Check if user already has an open shift on a different register
const _userHasOpenShiftElsewhere = computed(() => {
  if (!posStore.shift || posStore.shift.status !== "open") return false;
  if (!selectedRegisterId.value) return false;
  return posStore.shift.register_id !== selectedRegisterId.value;
});

// Opening balance form
const schema = toTypedSchema(
  object({
    opening_balance: number().required().min(0).default(0),
  }),
);

const { handleSubmit, errors, defineComponentBinds } = useForm({
  validationSchema: schema,
  initialValues: { opening_balance: 0 },
});

const openingBalance = defineComponentBinds("opening_balance");

function getRegisterStatus(register: CashRegister, currentUserId: number): RegisterStatus {
  if (register.status === "inactive") {
    return { state: "inactive", selectable: false, label: t("Inactive") };
  }
  if (register.current_shift?.status === "open") {
    if (register.current_shift.cashier_id === currentUserId) {
      return { state: "own-shift", selectable: true, label: t("Available") };
    }
    return {
      state: "in-use",
      selectable: false,
      label: t("In Use"),
    };
  }
  return { state: "available", selectable: true, label: t("Available") };
}

const selectedRegister = computed(() => {
  if (!selectedRegisterId.value) return null;
  return registers.value.find((r) => r.id === selectedRegisterId.value) ?? null;
});

const selectedRegisterStatus = computed(() => {
  if (!selectedRegister.value || !posStore.userId) return null;
  return getRegisterStatus(selectedRegister.value, posStore.userId);
});

// Show open shift form when a register without an open shift is selected
watch(selectedRegisterStatus, (status) => {
  if (status && status.state === "available") {
    showOpenShiftForm.value = true;
  } else {
    showOpenShiftForm.value = false;
  }
});

async function loadRegisters(): Promise<void> {
  loading.value = true;
  error.value = null;
  try {
    const storeId = posStore.store?.id;
    registers.value = await posClient.getRegisters(storeId);
  } catch (_err) {
    error.value = t("Failed to load registers");
  } finally {
    loading.value = false;
  }
}

// Load registers when dialog opens
watch(visible, (isVisible) => {
  if (isVisible && registers.value.length === 0) {
    loadRegisters();
  }
});

const onSelectAndContinue = handleSubmit(async (values) => {
  if (!selectedRegisterId.value) return;

  try {
    if (selectedRegisterStatus.value?.state === "own-shift") {
      // Continue existing shift
      const session = await posClient.selectRegister(selectedRegisterId.value);
      if (session.register) posStore.setRegister(session.register);
      if (session.shift) posStore.setShift(session.shift);
    } else {
      // Open new shift
      const session = await posClient.openShift(selectedRegisterId.value, values.opening_balance);
      if (session.register) posStore.setRegister(session.register);
      if (session.shift) posStore.setShift(session.shift);
    }
    visible.value = false;
  } catch {
    // Error handling is done in usePosClient
  }
});

function cancel(): void {
  router.visit(route("home"));
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Select Register')"
    :style="{ width: '520px' }"
    :breakpoints="{ '768px': '98vw' }"
    :closable="false"
    data-testid="register-select-dialog"
  >
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <ProgressSpinner style="width: 32px; height: 32px" />
      <span class="ml-2 text-surface-500">{{ t("Loading registers...") }}</span>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="text-center py-8">
      <i class="fa fa-times-circle text-4xl text-red-500 mb-4" aria-hidden="true" />
      <h3 class="text-lg font-semibold mb-2">{{ t("Failed to load registers") }}</h3>
      <p class="text-surface-500 dark:text-surface-400 mb-4">{{ error }}</p>
      <Button :label="t('Retry')" @click="loadRegisters" data-testid="retry-button" />
    </div>

    <!-- Empty state -->
    <div v-else-if="registers.length === 0" class="text-center py-8">
      <i class="fa fa-exclamation-triangle text-4xl text-yellow-500 mb-4" aria-hidden="true" />
      <h3 class="text-lg font-semibold mb-2">{{ t("No registers available") }}</h3>
      <p class="text-surface-500 dark:text-surface-400">
        {{ t("Please contact your manager to set up a register.") }}
      </p>
    </div>

    <!-- Register list -->
    <div v-else>
      <p class="text-sm text-surface-500 dark:text-surface-400 mb-4">{{ t("Store") }}: {{ storeName }}</p>

      <div class="flex flex-col gap-2 mb-4">
        <div
          v-for="reg in registers"
          :key="reg.id"
          class="flex items-center justify-between p-3 border rounded-lg border-surface-200 dark:border-surface-700"
          :class="{
            'opacity-50 cursor-not-allowed': !getRegisterStatus(reg, posStore.userId ?? 0).selectable,
            'bg-primary-50 dark:bg-primary-900/20 border-primary-300 dark:border-primary-700': selectedRegisterId === reg.id,
          }"
          data-testid="register-item"
        >
          <div class="flex items-center gap-3">
            <RadioButton
              v-model="selectedRegisterId"
              :value="reg.id"
              :disabled="!getRegisterStatus(reg, posStore.userId ?? 0).selectable"
              :data-testid="`register-radio-${reg.id}`"
            />
            <div>
              <span class="font-medium">{{ reg.name }}</span>
              <span class="text-sm text-surface-500 dark:text-surface-400 ml-2">({{ reg.code }})</span>
            </div>
          </div>
          <span
            class="text-sm"
            :class="{
              'text-surface-400': getRegisterStatus(reg, posStore.userId ?? 0).state === 'inactive',
              'text-yellow-600 dark:text-yellow-400': getRegisterStatus(reg, posStore.userId ?? 0).state === 'in-use',
              'text-green-600 dark:text-green-400':
                getRegisterStatus(reg, posStore.userId ?? 0).state === 'available' ||
                getRegisterStatus(reg, posStore.userId ?? 0).state === 'own-shift',
            }"
          >
            {{ getRegisterStatus(reg, posStore.userId ?? 0).label }}
          </span>
        </div>
      </div>

      <!-- Opening balance input (shown when register has no open shift) -->
      <div v-if="showOpenShiftForm" class="mb-4">
        <label for="opening-balance" class="block text-sm font-medium mb-2">
          {{ t("Opening Balance") }}
        </label>
        <InputNumber
          id="opening-balance"
          v-model="openingBalance.model.value"
          mode="currency"
          currency="BOB"
          :min="0"
          :min-fraction-digits="2"
          :max-fraction-digits="2"
          :class="{ 'p-invalid': errors.opening_balance }"
          data-testid="opening-balance-input"
        />
        <small v-if="errors.opening_balance" class="p-error">{{ errors.opening_balance }}</small>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <Button :label="t('Cancel')" severity="secondary" @click="cancel" data-testid="cancel-button" />
        <Button
          :label="showOpenShiftForm ? t('Open Shift') : t('Select & Continue')"
          :disabled="!selectedRegisterId || loading"
          @click="onSelectAndContinue"
          data-testid="select-button"
        />
      </div>
    </template>
  </Dialog>
</template>
