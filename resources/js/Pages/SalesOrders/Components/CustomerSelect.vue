<script setup lang="ts">
import { Button, InputText, Popover, useToast } from "primevue";
import { useI18n } from "vue-i18n";
import { ref, computed, onMounted, nextTick } from "vue";
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
const searchError = ref("");
const showCreateForm = ref(false);
const taxIdNameInput = ref("");
const creating = ref(false);
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
  showCreateForm.value = false;

  try {
    const response = await findByTaxIdApi<CustomerOption>(taxIdInput.value.trim());

    selectedCustomer.value = response.data;
    emit("update:modelValue", response.data.id);
    emit("select", response.data);
  } catch (err: unknown) {
    const status = (err as { response?: { status?: number } })?.response?.status;

    if (status === 404) {
      searchError.value = t("No customer found with that tax ID.");
      showCreateForm.value = true;
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
    showCreateForm.value = false;
    searchError.value = "";

    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Customer created successfully."),
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
  showCreateForm.value = false;
  searchError.value = "";
}

function clearCustomer() {
  selectedCustomer.value = null;
  taxIdInput.value = "";
  taxIdNameInput.value = "";
  searchError.value = "";
  showCreateForm.value = false;
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
  <div class="flex flex-col gap-1">
    <label for="customer-tax-id">{{ t("Customer") }}</label>

    <!-- Search input + button (shown when no customer selected) -->
    <div v-if="!selectedCustomer" class="flex gap-2">
      <div class="flex-1">
        <InputText
          id="customer-tax-id"
          v-model="taxIdInput"
          :placeholder="t('Enter tax ID')"
          :disabled="loading"
          autocomplete="off"
          class="w-full"
          @keydown.enter.prevent="searchCustomer"
        />
      </div>
      <Button
        icon="fa fa-search"
        :loading="loading"
        :disabled="loading || !taxIdInput.trim()"
        :aria-label="t('Search')"
        size="small"
        @click="searchCustomer"
      />
    </div>

    <!-- Error message -->
    <small v-if="searchError" role="alert" class="text-red-400 dark:text-red-300">
      {{ searchError }}
    </small>

    <!-- Inline create form -->
    <div v-if="showCreateForm" class="border border-surface-200 dark:border-surface-700 rounded p-3 mt-1 bg-surface-50 dark:bg-surface-950">
      <div class="flex flex-col gap-2">
        <div>
          <label for="customer-tax-id-name" class="text-sm font-medium">{{ t("Tax ID Name") }}</label>
          <InputText
            id="customer-tax-id-name"
            ref="taxIdNameInputRef"
            v-model="taxIdNameInput"
            :placeholder="t('Enter tax ID name')"
            :disabled="creating"
            class="w-full mt-1"
            @keydown.enter.prevent="createCustomer"
          />
        </div>
        <div class="flex gap-2">
          <Button
            icon="fa fa-check"
            :label="t('Create & Select')"
            :loading="creating"
            :disabled="creating || !taxIdNameInput.trim()"
            size="small"
            raised
            class="uppercase"
            @click="createCustomer"
          />
          <Button :label="t('Cancel')" severity="secondary" size="small" text @click="cancelCreate" />
        </div>
        <small class="text-surface-500 dark:text-surface-400 flex items-center gap-1">
          <i class="fa fa-circle-info" />
          {{ t("You can complete the customer's details later from the Customers page.") }}
        </small>
      </div>
    </div>

    <!-- Selected customer chip -->
    <div
      v-if="selectedCustomer"
      class="flex items-center gap-2 border border-surface-200 dark:border-surface-700 rounded px-3 py-2 bg-surface-50 dark:bg-surface-950"
    >
      <i class="fa fa-user-check text-surface-400" />
      <span class="font-medium flex-1">{{ displayLabel }}</span>
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
      <Button
        v-tooltip.top="t('Remove customer')"
        icon="fa fa-times"
        text
        size="small"
        severity="secondary"
        :aria-label="t('Remove customer')"
        @click="clearCustomer"
      />
    </div>

    <!-- Customer info popover -->
    <Popover ref="customerInfoPopover">
      <div v-if="selectedCustomer" class="p-4 w-72">
        <h4 class="text-lg font-semibold mb-3">{{ displayLabel }}</h4>
        <div class="flex flex-col gap-2 text-sm">
          <div v-if="selectedCustomer.email" class="flex items-center gap-2">
            <i class="fa fa-envelope text-surface-400 w-4 text-center" />
            <span>{{ selectedCustomer.email }}</span>
          </div>
          <div v-if="selectedCustomer.phone" class="flex items-center gap-2">
            <i class="fa fa-phone text-surface-400 w-4 text-center" />
            <span>{{ selectedCustomer.phone }}</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fa fa-id-card text-surface-400 w-4 text-center" />
            <span>{{ selectedCustomer.tax_id }}</span>
          </div>
        </div>
      </div>
    </Popover>

    <!-- Walk-in / no customer -->
    <small v-if="!modelValue && !selectedCustomer" class="text-surface-400">{{ t("No customer (Walk-in)") }}</small>
    <Button
      v-if="modelValue"
      type="button"
      icon="fa fa-times"
      :label="t('Walk-in')"
      severity="secondary"
      text
      size="small"
      @click="clearCustomer"
    />
  </div>
</template>
