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

const selectedRegister = computed(() => registers.value.find((register) => register.id === selectedRegisterId.value) ?? null);

// Opening balance form
const schema = toTypedSchema(
  object({
    opening_balance: number().required().min(0).default(0),
  }),
);

const { handleSubmit, errors, defineField } = useForm({
  validationSchema: schema,
  initialValues: { opening_balance: 0 },
});

const [openingBalance, openingBalanceAttrs] = defineField("opening_balance");

async function loadRegisters(): Promise<void> {
  loading.value = true;
  error.value = null;
  try {
    const storeId = posStore.store?.id;
    registers.value = await posClient.getRegisters(storeId);
  } catch (err) {
    error.value = err instanceof Error ? t(err.message) : t("Failed to load registers");
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
    const session = await posClient.openShift(selectedRegisterId.value, values.opening_balance);
    if (session.store) posStore.setStore(session.store);
    if (session.register) posStore.setRegister(session.register);
    if (session.shift) posStore.setShift(session.shift);
    visible.value = false;
  } catch (err) {
    error.value = err instanceof Error ? t(err.message) : t("Failed to open shift");
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
        <div class="flex flex-col gap-2 mb-4">
        <div
          v-for="reg in registers"
          :key="reg.id"
          class="flex items-center justify-between p-3 border rounded-lg border-surface-200 dark:border-surface-700"
          :class="{
            'bg-primary-50 dark:bg-primary-900/20 border-primary-300 dark:border-primary-700': selectedRegisterId === reg.id,
          }"
          data-testid="register-item"
        >
          <div class="flex items-center gap-3">
            <RadioButton
              v-model="selectedRegisterId"
              :value="reg.id"
              :data-testid="`register-radio-${reg.id}`"
            />
            <div>
              <span class="font-medium">{{ reg.name }}</span>
              <span class="text-sm text-surface-500 dark:text-surface-400 ml-2">({{ reg.code }})</span>
              <p class="text-sm text-surface-500 dark:text-surface-400">{{ reg.store?.name }}</p>
            </div>
          </div>
          <span class="text-sm text-green-600 dark:text-green-400">{{ t("Available") }}</span>
        </div>
      </div>

      <div v-if="selectedRegister" class="mb-4">
        <label for="opening-balance" class="block text-sm font-medium mb-2">
          {{ t("Opening Balance") }}
        </label>
        <InputNumber
          id="opening-balance"
          v-model="openingBalance"
          v-bind="openingBalanceAttrs"
          mode="currency"
          currency="BOB"
          :min="0"
          :min-fraction-digits="2"
          :max-fraction-digits="2"
          class="w-full"
          input-class="w-full"
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
          :label="t('Open Shift')"
          :disabled="!selectedRegisterId || loading"
          @click="onSelectAndContinue"
          data-testid="select-button"
        />
      </div>
    </template>
  </Dialog>
</template>
