<script setup lang="ts">
import PosLayout from "@layouts/pos.vue";
import CustomerForm from "@pages/Customers/Components/CustomerForm.vue";
import { route } from "ziggy-js";
defineOptions({ layout: PosLayout });
</script>
<template>
  <div class="min-h-[calc(100dvh-3.5rem)] p-3 sm:p-4 lg:min-h-full lg:p-6"><CustomerForm :back-url="route('pos.customers.index')" :submit-url="route('pos.customers.store')" method="post" toast-group="pos" /></div>
</template>
