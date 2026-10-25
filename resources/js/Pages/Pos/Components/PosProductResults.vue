<script setup lang="ts">
import PosProductResultItem from "./PosProductResultItem.vue";
import type { PosProductView } from "@composables/usePosProductView";
import type { PosSellableUnit } from "@/Types/pos";

defineProps<{
  units: PosSellableUnit[];
  view: PosProductView;
  getRemainingBase: (variantId: number) => number | null;
}>();

const emit = defineEmits<{
  (e: "add", unit: PosSellableUnit): void;
  (e: "info", event: Event, unit: PosSellableUnit): void;
}>();
</script>

<template>
  <ul
    :class="
      view === 'grid'
        ? 'grid grid-cols-[repeat(auto-fill,minmax(max(9.5rem,calc((100%_-_24px)/3)),1fr))] gap-3 p-4'
        : 'divide-y divide-surface-200 dark:divide-surface-700'
    "
  >
    <li v-for="unit in units" :key="unit.key" :class="view === 'grid' ? 'min-w-0' : 'p-4'">
      <PosProductResultItem
        :unit="unit"
        :view="view"
        :get-remaining-base="getRemainingBase"
        @add="emit('add', $event)"
        @info="(event, unit) => emit('info', event, unit)"
      />
    </li>
  </ul>
</template>