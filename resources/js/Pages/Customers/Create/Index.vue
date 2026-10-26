<script setup lang="ts">
import AppLayout from "@layouts/admin.vue";
import CustomerForm from "../Components/CustomerForm.vue";
import { route } from "ziggy-js";
defineOptions({ layout: AppLayout });
</script>
<template><CustomerForm :back-url="route('customers')" :submit-url="route('customers.store')" method="post" /></template>
