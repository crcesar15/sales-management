<script setup lang="ts">
import AppLayout from "@layouts/admin.vue";
import CustomerForm from "../Components/CustomerForm.vue";
import { route } from "ziggy-js";
import type { Customer } from "@/Types/customer-types";
defineOptions({ layout: AppLayout });
const props = defineProps<{ customer: Customer }>();
</script>
<template><CustomerForm :customer="props.customer" :back-url="route('customers')" :submit-url="route('customers.update', props.customer.id)" method="put" /></template>
