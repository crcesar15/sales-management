<script>

import AppMenuItem from "./AppMenuItem.vue";

export default {
  components: {
    AppMenuItem,
  },
  inject: ["menuitems"],
};
</script>

<template>
  <ul class="layout-menu">
    <template
      v-for="(item, i) in menuitems"
      :key="item"
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
    <li>
      <a
        href="https://www.primefaces.org/primeblocks-vue/#/"
        target="_blank"
      >
        <img
          src=""
          alt="Prime Blocks"
          class="w-full mt-3"
        >
      </a>
    </li>
  </ul>
</template>

<style lang="scss" scoped></style>
