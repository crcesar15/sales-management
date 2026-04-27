<script setup lang="ts">
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import type { TransferStatus } from "@/Types/stock-transfer-types";

const props = defineProps<{
  currentStatus: TransferStatus;
  cancelled?: boolean;
}>();

const { t } = useI18n();

const steps = [
  { key: "requested", label: "Requested" },
  { key: "picked", label: "Picked" },
  { key: "in_transit", label: "In Transit" },
  { key: "received", label: "Received" },
  { key: "completed", label: "Completed" },
];

const currentIndex = computed(() => steps.findIndex((s) => s.key === props.currentStatus));
</script>

<template>
  <div v-if="cancelled" class="flex items-center gap-2">
    <div
      v-for="(step, i) in steps"
      :key="step.key"
      class="flex items-center gap-2"
    >
      <div
        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300"
      >
        <i class="fa-solid fa-xmark text-xs" />
      </div>
      <span class="text-sm text-red-500">{{ t(step.label) }}</span>
      <i v-if="i < steps.length - 1" class="fa-solid fa-chevron-right text-xs text-surface-300" />
    </div>
  </div>
  <div v-else class="flex items-center gap-2">
    <div
      v-for="(step, i) in steps"
      :key="step.key"
      class="flex items-center gap-2"
    >
      <div
        :class="[
          'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
          i <= currentIndex
            ? 'bg-primary-500 text-white'
            : 'bg-surface-200 text-surface-500 dark:bg-surface-700 dark:text-surface-400',
        ]"
      >
        {{ i + 1 }}
      </div>
      <span
        :class="[
          'text-sm',
          i <= currentIndex ? 'text-primary-600 font-medium' : 'text-surface-400',
        ]"
      >
        {{ t(step.label) }}
      </span>
      <i
        v-if="i < steps.length - 1"
        :class="[
          'fa-solid fa-chevron-right text-xs',
          i < currentIndex ? 'text-primary-400' : 'text-surface-300',
        ]"
      />
    </div>
  </div>
</template>
