<script setup lang="ts">
import { Card, Button, Tag, DataTable, Column, Toast, useToast } from "primevue";
import AppLayout from "@layouts/admin.vue";
import MovementForm from "@pages/CashRegisterShifts/Components/MovementForm.vue";
import CloseShiftDialog from "@pages/CashRegisterShifts/Components/CloseShiftDialog.vue";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { CashRegisterShiftResponse, CashRegisterMovementResponse } from "@/Types/cash-register-types";
import { useI18n } from "vue-i18n";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  shift: CashRegisterShiftResponse;
}>();

const { t } = useI18n();
const toast = useToast();
const { formatCurrencySymbol } = useCurrencyFormatter();
const { formatDatetime } = useDatetimeFormatter();

const showMovementDialog = ref(false);
const showCloseDialog = ref(false);
const isForceClose = ref(false);

function shiftStatusSeverity(status: string) {
  switch (status) {
    case "open":
      return "success";
    case "closed":
      return "info";
    case "forced_close":
      return "danger";
    default:
      return "secondary";
  }
}

function shiftStatusLabel(status: string) {
  switch (status) {
    case "open":
      return t("Open");
    case "closed":
      return t("Closed");
    case "forced_close":
      return t("Forced Close");
    default:
      return status;
  }
}

function movementTypeSeverity(type: string) {
  return type === "cash_in" ? "success" : "danger";
}

function movementTypeLabel(type: string) {
  return type === "cash_in" ? t("Cash In") : t("Cash Out");
}

const differenceClass = computed(() => {
  const diff = props.shift.difference;
  if (diff === null) return "";
  if (diff === 0) return "text-green-600 dark:text-green-400 font-bold";
  if (diff > 0) return "text-amber-600 dark:text-amber-400 font-bold";
  return "text-red-600 dark:text-red-400 font-bold";
});

const onShiftClosed = () => {
  showCloseDialog.value = false;
  router.visit(route("shifts.show", props.shift.id), { preserveState: false });
};

const onMovementAdded = () => {
  showMovementDialog.value = false;
  router.visit(route("shifts.show", props.shift.id), { preserveState: false });
};

const openCloseDialog = (force: boolean) => {
  isForceClose.value = force;
  showCloseDialog.value = true;
};
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="router.visit(route('shifts'))" />
        <h2 class="text-2xl font-bold m-0">{{ t("Shift Details") }}</h2>
        <Tag :severity="shiftStatusSeverity(shift.status)" :value="shiftStatusLabel(shift.status)" />
      </div>
      <Button
        v-if="shift.status === 'open'"
        v-can="'shift.close'"
        :label="t('Close Shift')"
        icon="fa fa-lock"
        raised
        @click="openCloseDialog(false)"
      />
    </div>

    <Toast />

    <div class="grid grid-cols-12 gap-4">
      <!-- Main column: Movements -->
      <div class="col-span-12 lg:col-span-8">
        <Card>
          <template #title>
            <div class="flex justify-between items-center">
              <span>{{ t("Movements") }}</span>
              <Button
                v-if="shift.status === 'open'"
                v-can="'cash_movement.create'"
                :label="t('Add Movement')"
                icon="fa fa-plus"
                size="small"
                @click="showMovementDialog = true"
              />
            </div>
          </template>
          <template #content>
            <DataTable
              v-if="shift.movements && shift.movements.length > 0"
              :value="shift.movements"
              resizable-columns
              class="border-t-2 border-surface-200 dark:border-surface-700"
            >
              <Column field="type" :header="t('Movement Type')">
                <template #body="{ data }: { data: CashRegisterMovementResponse }">
                  <Tag :severity="movementTypeSeverity(data.type)" :value="movementTypeLabel(data.type)" />
                </template>
              </Column>
              <Column field="amount" :header="t('Amount')">
                <template #body="{ data }: { data: CashRegisterMovementResponse }">
                  <span :class="data.type === 'cash_in' ? 'text-green-600 dark:text-green-400 font-medium' : 'text-red-600 dark:text-red-400 font-medium'">
                    {{ data.type === "cash_in" ? "+" : "-" }}{{ formatCurrencySymbol(String(data.amount)) }}
                  </span>
                </template>
              </Column>
              <Column field="reason" :header="t('Reason')" />
              <Column field="user" :header="t('Cashier')">
                <template #body="{ data }: { data: CashRegisterMovementResponse }">
                  {{ data.user?.full_name ?? "---" }}
                </template>
              </Column>
              <Column field="created_at" :header="t('Created At')">
                <template #body="{ data }: { data: CashRegisterMovementResponse }">
                  {{ formatDatetime(data.created_at) }}
                </template>
              </Column>
            </DataTable>
            <div v-else class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-receipt text-4xl mb-3"></i>
              <span>{{ t("No movements recorded") }}</span>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sidebar: Info + Financial Summary -->
      <div class="col-span-12 lg:col-span-4">
        <Card class="mb-4">
          <template #title>{{ t("Shift Info") }}</template>
          <template #content>
            <div class="flex flex-col gap-3">
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Cash Register") }}</span>
                <span class="font-medium">{{ shift.cash_register?.name ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Cashier") }}</span>
                <span class="font-medium">{{ shift.user?.full_name ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Opening Time") }}</span>
                <span class="font-medium">{{ formatDatetime(shift.opened_at) }}</span>
              </div>
              <div v-if="shift.closed_at">
                <span class="text-sm text-surface-500 block">{{ t("Closing Time") }}</span>
                <span class="font-medium">{{ formatDatetime(shift.closed_at) }}</span>
              </div>
              <div v-if="shift.notes">
                <span class="text-sm text-surface-500 block">{{ t("Notes") }}</span>
                <span class="font-medium">{{ shift.notes }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Financial Summary") }}</template>
          <template #content>
            <div class="flex flex-col gap-3">
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Opening Balance") }}</span>
                <span class="font-bold">{{ formatCurrencySymbol(String(shift.opening_balance)) }}</span>
              </div>
              <div v-if="shift.closing_balance !== null" class="flex justify-between">
                <span class="text-surface-500">{{ t("Closing Balance") }}</span>
                <span class="font-bold">{{ formatCurrencySymbol(String(shift.closing_balance)) }}</span>
              </div>
              <div v-if="shift.expected_closing !== null" class="flex justify-between">
                <span class="text-surface-500">{{ t("Expected Closing") }}</span>
                <span class="font-bold">{{ formatCurrencySymbol(String(shift.expected_closing)) }}</span>
              </div>
              <div v-if="shift.difference !== null" class="flex justify-between pt-2 border-t border-surface-200 dark:border-surface-700">
                <span class="text-surface-500">{{ t("Difference") }}</span>
                <span :class="differenceClass">{{ formatCurrencySymbol(String(shift.difference)) }}</span>
              </div>
            </div>
          </template>
        </Card>

        <div v-if="shift.status === 'open'">
          <Button
            v-can="'shift.manage'"
            :label="t('Force Close Shift')"
            icon="fa fa-exclamation-triangle"
            severity="secondary"
            class="w-full"
            @click="openCloseDialog(true)"
          />
        </div>
      </div>
    </div>

    <!-- Dialogs -->
    <MovementForm
      v-model:visible="showMovementDialog"
      :shift-id="shift.id"
      @movement-added="onMovementAdded"
    />
    <CloseShiftDialog
      v-model:visible="showCloseDialog"
      :shift="shift"
      :force-close="isForceClose"
      @shift-closed="onShiftClosed"
    />
  </div>
</template>