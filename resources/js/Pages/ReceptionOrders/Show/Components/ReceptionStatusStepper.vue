<script setup lang="ts">
import { Stepper, StepList, Step } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import type { ReceptionOrderStatus } from "@/Types/reception-order-types";

const props = defineProps<{ currentStatus: ReceptionOrderStatus }>();
const { t } = useI18n();

const stepperSteps = [
  { key: "pending", value: "1", label: "Pending" },
  { key: "uncompleted", value: "2", label: "Uncompleted" },
  { key: "completed", value: "3", label: "Completed" },
];

const isCancelled = computed(() => props.currentStatus === "cancelled");

const activeStepValue = computed(() => {
  if (isCancelled.value) return undefined;
  const index = stepperSteps.findIndex((s) => s.key === props.currentStatus);
  return index >= 0 ? String(index + 1) : "1";
});

const stepperPt = computed(() => {
  const pt: Record<string, Record<string, string>> = {
    root: { class: "pointer-events-none" },
    step: { class: "pointer-events-none" },
  };
  if (isCancelled.value) {
    pt.root.class += " opacity-100";
    pt.separator = { class: "!bg-red-300 dark:!bg-red-700" };
  }
  return pt;
});
</script>

<template>
  <Stepper :value="activeStepValue" :pt="stepperPt" class="mb-4 hidden xl:block">
    <StepList>
      <Step v-for="step in stepperSteps" :key="step.key" :value="step.value" :class="{ '!text-red-500': isCancelled }">
        {{ t(step.label) }}
      </Step>
    </StepList>
  </Stepper>
</template>