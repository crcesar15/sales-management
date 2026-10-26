<script setup lang="ts">
import PosLayout from "@layouts/pos.vue";
import CustomerList from "@pages/Customers/Components/CustomerList.vue";
import { route } from "ziggy-js";
import type { CustomerResponse } from "@/Types/customer-types";
import { usePosStore } from "@composables/usePosStore";

defineOptions({ layout: PosLayout });

defineProps<{
  customers: { data: CustomerResponse[]; meta: { current_page: number; last_page: number; per_page: number; total: number } };
  filters: { filter?: string | null; status?: string; order_by?: string; order_direction?: string; per_page?: number };
}>();

const routes = {
  index: route("pos.customers.index"),
  create: route("pos.customers.create"),
  edit: (id: number) => route("pos.customers.edit", id),
  destroy: (id: number) => route("pos.customers.destroy", id),
};
const posStore = usePosStore();

function handleDeleted(customerId: number): void {
  if (posStore.customer?.id === customerId) posStore.clearCustomer();
}
</script>

<template>
  <div class="min-h-[calc(100dvh-3.5rem)] p-3 sm:p-4 lg:min-h-full lg:p-6">
    <CustomerList :customers="customers" :filters="filters" :routes="routes" toast-group="pos" @deleted="handleDeleted" />
  </div>
</template>
