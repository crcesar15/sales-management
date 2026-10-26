<script setup lang="ts">
import AppLayout from "@layouts/admin.vue";
import CustomerList from "./Components/CustomerList.vue";
import { route } from "ziggy-js";
import type { CustomerResponse } from "@/Types/customer-types";
defineOptions({ layout: AppLayout });
defineProps<{ customers: { data: CustomerResponse[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }; filters: { filter?: string | null; status?: string; order_by?: string; order_direction?: string; per_page?: number } }>();
const routes = { index: route("customers"), create: route("customers.create"), edit: (id: number) => route("customers.edit", id), destroy: (id: number) => route("customers.destroy", id) };
</script>
<template><CustomerList :customers="customers" :filters="filters" :routes="routes" /></template>
