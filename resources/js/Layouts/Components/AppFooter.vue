<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();

const appName = computed<string>(() => page.props.appConfig?.name || "Sales Management");
</script>

<template>
  <div class="layout-footer">
    {{ appName }} powered by
    <a href="https://primevue.org" target="_blank" rel="noopener noreferrer" class="text-primary font-bold hover:underline">PrimeVue</a>
  </div>
</template>
