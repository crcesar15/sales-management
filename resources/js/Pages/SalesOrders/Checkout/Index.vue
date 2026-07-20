<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { SalesOrderResponse } from "@/Types/sales-order-types";

const props = defineProps<{ order: SalesOrderResponse }>();

// Kept only as a compatibility bridge for server redirects from legacy checkout
// routes. All sales-order workflow UI now lives in Edit.
router.visit(route("sales-orders.edit", props.order.id), { replace: true });
</script>

<template><div /></template>
