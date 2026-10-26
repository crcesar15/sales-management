<script setup lang="ts">
import PosLayout from "@layouts/pos.vue";
import CustomerForm from "@pages/Customers/Components/CustomerForm.vue";
import { route } from "ziggy-js";
import type { Customer } from "@/Types/customer-types";
import type { CustomerPayload } from "@/Types/customer-types";
import { usePosStore } from "@composables/usePosStore";
defineOptions({ layout: PosLayout });
const props = defineProps<{ customer: Customer }>();
const posStore = usePosStore();

function syncSelectedCustomer(payload: CustomerPayload): void {
  posStore.updateCustomer(props.customer.id, payload);
}
</script>
<template>
  <div class="min-h-[calc(100dvh-3.5rem)] p-3 sm:p-4 lg:min-h-full lg:p-6"><CustomerForm :customer="props.customer" :back-url="route('pos.customers.index')" :submit-url="route('pos.customers.update', props.customer.id)" method="put" toast-group="pos" @saved="syncSelectedCustomer" /></div>
</template>
