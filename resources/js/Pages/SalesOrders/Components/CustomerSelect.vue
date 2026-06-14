<script setup lang="ts">
import { AutoComplete, Button, Popover } from "primevue";
import { useI18n } from "vue-i18n";
import { ref, computed } from "vue";
import type { CustomerOption } from "@/Types/sales-order-types";

const props = defineProps<{
  modelValue: number | null;
  customers: CustomerOption[];
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: number | null): void;
  (e: "select", customer: CustomerOption | null): void;
}>();

const { t } = useI18n();
const searchQuery = ref("");
const filteredCustomers = ref<CustomerOption[]>([]);
const customerInfoPopover = ref();

const selectedCustomer = computed(() => {
  if (!props.modelValue) return null;
  return props.customers.find((c) => c.id === props.modelValue) ?? null;
});

const displayValue = computed(() => {
  if (selectedCustomer.value) {
    return {
      id: selectedCustomer.value.id,
      first_name: selectedCustomer.value.first_name,
      last_name: selectedCustomer.value.last_name,
      email: selectedCustomer.value.email,
      phone: selectedCustomer.value.phone,
      tax_id: selectedCustomer.value.tax_id,
      display_label: `${selectedCustomer.value.first_name} ${selectedCustomer.value.last_name}`.trim(),
    };
  }
  return null;
});

function onSearch(event: { query: string }) {
  const q = event.query.toLowerCase();
  if (!q) {
    filteredCustomers.value = props.customers.slice(0, 20);
    return;
  }
  filteredCustomers.value = props.customers.filter(
    (c) =>
      c.first_name.toLowerCase().includes(q) ||
      c.last_name.toLowerCase().includes(q) ||
      (c.email && c.email.toLowerCase().includes(q)) ||
      (c.phone && c.phone.toLowerCase().includes(q)),
  );
}

function onSelect(event: { value: CustomerOption & { display_label?: string } }) {
  emit("update:modelValue", event.value.id);
  emit("select", event.value);
}

function clear() {
  emit("update:modelValue", null);
  emit("select", null);
  searchQuery.value = "";
}

function toggleCustomerInfo(event: Event) {
  customerInfoPopover.value.toggle(event);
}
</script>

<template>
  <div class="flex flex-col gap-1">
    <label for="customer">{{ t("Customer") }}</label>
    <div class="flex">
      <div class="flex-1">
        <AutoComplete
          id="customer"
          :model-value="displayValue"
          :suggestions="filteredCustomers"
          option-label="display_label"
          :placeholder="t('Select customer')"
          :empty-search-message="t('No available options')"
          force-selection
          class="w-full"
          @complete="onSearch"
          @option-select="onSelect"
        >
          <template #option="{ option }">
            <div class="flex items-center gap-2">
              <i class="fa fa-user text-surface-400" />
              <div>
                <span class="font-medium">{{ option.first_name }} {{ option.last_name }}</span>
                <div v-if="option.email" class="text-sm text-surface-500">{{ option.email }}</div>
              </div>
            </div>
          </template>
        </AutoComplete>
      </div>
      <Button
        v-if="selectedCustomer?.id"
        v-tooltip.top="t('Customer Information')"
        icon="fa fa-eye"
        text
        size="small"
        @click="toggleCustomerInfo"
      />
    </div>
    <Popover ref="customerInfoPopover">
      <div v-if="selectedCustomer" class="p-4 w-72">
        <h4 class="text-lg font-semibold mb-3">{{ selectedCustomer.first_name }} {{ selectedCustomer.last_name }}</h4>
        <div class="flex flex-col gap-2 text-sm">
          <div v-if="selectedCustomer.email" class="flex items-center gap-2">
            <i class="fa fa-envelope text-surface-400 w-4 text-center" />
            <span>{{ selectedCustomer.email }}</span>
          </div>
          <div v-if="selectedCustomer.phone" class="flex items-center gap-2">
            <i class="fa fa-phone text-surface-400 w-4 text-center" />
            <span>{{ selectedCustomer.phone }}</span>
          </div>
          <div v-if="selectedCustomer.tax_id" class="flex items-center gap-2">
            <i class="fa fa-id-card text-surface-400 w-4 text-center" />
            <span>{{ selectedCustomer.tax_id }}</span>
          </div>
        </div>
      </div>
    </Popover>
    <small v-if="!modelValue" class="text-surface-400">{{ t("No customer (Walk-in)") }}</small>
    <Button
      v-if="modelValue"
      type="button"
      icon="fa fa-times"
      :label="t('Walk-in')"
      severity="secondary"
      text
      size="small"
      @click="clear"
    />
  </div>
</template>