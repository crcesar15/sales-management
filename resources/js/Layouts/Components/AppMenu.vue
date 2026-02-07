<script setup lang="ts">
import { inject } from "vue";
import type { MenuItemType } from "../Types/menu";
import AppMenuItem from "./AppMenuItem.vue";

const model = inject<MenuItemType[]>("menuitems", []);
</script>

<template>
  <ul class="layout-menu">
    <template
      v-for="(item, i) in model"
      :key="item.label"
    >
      <app-menu-item
        v-if="!item.separator"
        :item="item"
        :index="i"
      />
      <li
        v-if="item.separator"
        class="menu-separator"
      />
    </template>
  </ul>
</template>

<style lang="scss" scoped></style>
