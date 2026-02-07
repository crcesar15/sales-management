<script setup lang="ts">
import { provide } from "vue";
import AppLayout from "./components/AppLayout.vue";
import { useMenuItems } from "@composables/useMenuItems";

const { menuItems, userActions } = useMenuItems();

// Provide menu items to child components (for backward compatibility with existing components)
provide("menuitems", menuItems.value);
provide("userActions", userActions.value);
</script>

<template>
  <app-layout>
    <slot />
  </app-layout>
</template>

<style scoped>
.sidebar-icons {
  border-radius: 5px;
  padding: 2px;
  border: solid 1px #4b5563;
  width: 28px;
  text-align: center;
  height: 28px;
}

.sidebar-icons::before {
  vertical-align: sub;
}

.hidden-sidebar {
  display: none;
}

.visible-sidebar {
  display: block;
}
</style>
