<script setup lang="ts">
import { Button, Dialog, InputText, Message, Popover, useToast } from "primevue";
import { useI18n } from "vue-i18n";
import { ref, computed, onMounted, nextTick, watch } from "vue";
import { route } from "ziggy-js";
import { useCustomerClient } from "@composables/useCustomerClient";
import type { CustomerOption } from "@/Types/sales-order-types";

const props = defineProps<{
  modelValue: number | null;
  initialCustomer?: CustomerOption | null;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: number | null): void;
  (e: "select", customer: CustomerOption | null): void;
}>();

const { t } = useI18n();
const toast = useToast();
const { loading, findByTaxIdApi, storeCustomerApi } = useCustomerClient();

const taxIdInput = ref("");
const selectedCustomer = ref<CustomerOption | null>(null);
const walkInSelected = ref(false);
const searchError = ref("");
const customerCreateVisible = ref(false);
const taxIdNameInput = ref("");
const creating = ref(false);
const taxIdInputRef = ref<HTMLInputElement | null>(null);
const taxIdNameInputRef = ref<HTMLInputElement | null>(null);
const customerInfoPopover = ref();

function buildLabel(c: CustomerOption): string {
  return `${c.tax_id} — ${c.tax_id_name}`;
}

const displayLabel = computed(() => (selectedCustomer.value ? buildLabel(selectedCustomer.value) : ""));

onMounted(() => {
  if (props.initialCustomer) {
    selectedCustomer.value = props.initialCustomer;
    taxIdInput.value = props.initialCustomer.tax_id;
  }
});

async function searchCustomer() {
  if (!taxIdInput.value.trim()) {
    return;
  }

  searchError.value = "";
  walkInSelected.value = false;

  try {
    const response = await findByTaxIdApi<CustomerOption>(taxIdInput.value.trim());

    selectedCustomer.value = response.data;
    emit("update:modelValue", response.data.id);
    emit("select", response.data);
  } catch (err: unknown) {
    const status = (err as { response?: { status?: number } })?.response?.status;

    if (status === 404) {
      searchError.value = t("No customer found for this Tax ID. Create one to assign it to this order.");
      taxIdNameInput.value = "";
      customerCreateVisible.value = true;
      nextTick(() => taxIdNameInputRef.value?.focus());
    } else {
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t("An error occurred while searching for the customer."),
        life: 3000,
      });
    }
  }
}

async function createCustomer() {
  if (!taxIdNameInput.value.trim()) {
    return;
  }

  creating.value = true;

  try {
    const response = await storeCustomerApi({
      tax_id: taxIdInput.value.trim(),
      tax_id_name: taxIdNameInput.value.trim(),
      status: "active",
    });

    selectedCustomer.value = response.data;
    emit("update:modelValue", response.data.id);
    emit("select", response.data);
    searchError.value = "";
    customerCreateVisible.value = false;

    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Customer created and assigned to this order."),
      life: 3000,
    });
  } catch {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t("Could not create the customer."),
      life: 3000,
    });
  } finally {
    creating.value = false;
  }
}

function cancelCreate() {
  customerCreateVisible.value = false;
}

watch(customerCreateVisible, (visible) => {
  if (visible) return;

  searchError.value = "";
  taxIdNameInput.value = "";
  nextTick(() => taxIdInputRef.value?.focus());
});

function selectWalkIn() {
  selectedCustomer.value = null;
  taxIdInput.value = "";
  taxIdNameInput.value = "";
  searchError.value = "";
  walkInSelected.value = true;
  emit("update:modelValue", null);
  emit("select", null);
}

function clearCustomer() {
  selectedCustomer.value = null;
  taxIdInput.value = "";
  taxIdNameInput.value = "";
  searchError.value = "";
  walkInSelected.value = false;
  emit("update:modelValue", null);
  emit("select", null);
}

function toggleCustomerInfo(event: Event) {
  customerInfoPopover.value.toggle(event);
}

function goToCustomerEdit() {
  if (selectedCustomer.value) {
    const url = route("customers.edit", { customer: selectedCustomer.value.id });
    window.open(url, "_blank");
  }
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <label for="customer-tax-id">{{ t("Customer Tax ID") }}</label>

    <div
      v-if="selectedCustomer || walkInSelected"
      class="flex min-w-0 items-center gap-2 rounded border border-surface-200 bg-surface-50 px-3 dark:border-surface-700 dark:bg-surface-950"
    >
      <i :class="selectedCustomer ? 'fa fa-user-check' : 'fa fa-person-walking'" class="text-surface-500 dark:text-surface-400" />
      <span class="min-w-0 flex-1 truncate font-medium">
        <template v-if="selectedCustomer">{{ displayLabel }}</template>
        <template v-else-if="walkInSelected">{{ t("Walk-in") }}</template>
        <template v-else>{{ t("No customer selected") }}</template>
      </span>
      <template v-if="selectedCustomer">
        <Button
          v-tooltip.top="t('Customer Information')"
          icon="fa fa-eye"
          text
          size="small"
          :aria-label="t('Customer Information')"
          @click="toggleCustomerInfo"
        />
        <Button
          v-tooltip.top="t('Edit customer details')"
          icon="fa fa-pen"
          text
          size="small"
          :aria-label="t('Edit customer details')"
          @click="goToCustomerEdit"
        />
      </template>
      <Button
        icon="fa fa-delete-left"
        v-tooltip.top="t('Clear customer')"
        text
        size="small"
        :aria-label="t('Clear customer')"
        @click="clearCustomer"
      />
    </div>

    <div v-else class="flex gap-2">
      <InputText
        id="customer-tax-id"
        ref="taxIdInputRef"
        v-model="taxIdInput"
        :placeholder="t('Enter tax ID')"
        :disabled="loading"
        autocomplete="off"
        class="min-w-0 flex-1"
        @keydown.enter.prevent="searchCustomer"
      />
      <Button
        icon="fa fa-search"
        :loading="loading"
        :disabled="loading || !taxIdInput.trim()"
        :aria-label="t('Search')"
        @click="searchCustomer"
      />
      <Button
        v-tooltip.top="t('Sell to a walk-in customer')"
        icon="fa fa-person-walking"
        severity="secondary"
        text
        :aria-label="t('Sell to a walk-in customer')"
        @click="selectWalkIn"
      />
    </div>

    <Dialog v-model:visible="customerCreateVisible" modal :header="t('Create and assign customer')" class="w-full max-w-xl">
      <div class="flex flex-col gap-4">
        <Message v-if="searchError" severity="warn" icon="fa fa-triangle-exclamation" :closable="false">
          {{ searchError }}
        </Message>

        <div class="flex flex-col gap-1">
          <label for="customer-tax-id-inherited" class="text-sm font-medium">{{ t("Customer Tax ID") }}</label>
          <InputText id="customer-tax-id-inherited" :model-value="taxIdInput" disabled class="w-full" />
        </div>

        <div class="flex flex-col gap-1">
          <label for="customer-tax-id-name" class="text-sm font-medium">{{ t("Tax ID Name") }}</label>
          <InputText
            id="customer-tax-id-name"
            ref="taxIdNameInputRef"
            v-model="taxIdNameInput"
            :placeholder="t('Enter tax ID name')"
            :disabled="creating"
            class="w-full"
            @keydown.enter.prevent="createCustomer"
          />
        </div>
        <small class="flex items-center gap-1 text-surface-500 dark:text-surface-400">
          <i class="fa fa-circle-info" />
          {{ t("The new customer will be assigned to this order. You can complete their details later from the Customers page.") }}
        </small>
      </div>
      <template #footer>
        <Button :label="t('Cancel')" severity="secondary" text @click="cancelCreate" />
        <Button
          icon="fa fa-check"
          :label="t('Create and assign')"
          :loading="creating"
          :disabled="creating || !taxIdNameInput.trim()"
          raised
          class="uppercase"
          @click="createCustomer"
        />
      </template>
    </Dialog>

    <!-- Customer info popover -->
    <Popover ref="customerInfoPopover">
      <div v-if="selectedCustomer" class="p-4 w-72">
        <h4 class="text-lg font-semibold mb-3">{{ displayLabel }}</h4>
        <div class="flex flex-col gap-2 text-sm">
          <div v-if="selectedCustomer.email" class="flex items-center gap-2">
            <i class="fa fa-envelope text-surface-500 dark:text-surface-400 w-4 text-center" />
            <span>{{ selectedCustomer.email }}</span>
          </div>
          <div v-if="selectedCustomer.phone" class="flex items-center gap-2">
            <i class="fa fa-phone text-surface-500 dark:text-surface-400 w-4 text-center" />
            <span>{{ selectedCustomer.phone }}</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fa fa-id-card text-surface-500 dark:text-surface-400 w-4 text-center" />
            <span>{{ selectedCustomer.tax_id }}</span>
          </div>
        </div>
      </div>
    </Popover>
  </div>
</template>
