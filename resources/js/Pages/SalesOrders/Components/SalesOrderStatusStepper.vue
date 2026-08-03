<script setup lang="ts">
import { Step, StepList, Stepper } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import type { SalesOrderStatus } from "@/Types/sales-order-types";
import OrderStatusBadge from "./OrderStatusBadge.vue";

const props = defineProps<{ status?: SalesOrderStatus }>();
const { t } = useI18n();

const steps = [
  { status: "draft", label: "Draft" },
  { status: "validated", label: "Validated" },
  { status: "fulfilled", label: "Fulfilled" },
  { status: "completed", label: "Completed" },
] as const;

const activeStep = computed(() => String(Math.max(1, steps.findIndex((step) => step.status === props.status) + 1)));
const stepperPt = { root: { class: "pointer-events-none" }, step: { class: "pointer-events-none" } };
</script>

<template>
  <div v-if="status" class="mb-0">
    <div class="flex items-center gap-2 xl:hidden justify-end">
      <span class="text-lg text-surface-500 dark:text-surface-400">{{ t("Status") }}</span>
      <OrderStatusBadge :status="status" />
    </div>
    <Stepper v-if="status !== 'cancelled'" :value="activeStep" :pt="stepperPt" class="hidden xl:block">
      <StepList>
        <Step v-for="(step, index) in steps" :key="step.status" :value="String(index + 1)">
          {{ t(step.label) }}
        </Step>
      </StepList>
    </Stepper>
  </div>
</template>
