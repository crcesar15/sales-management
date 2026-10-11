<script setup lang="ts">
import { Chip, Column, DataTable, Paginator, Tag } from "primevue";
import type { PageState } from "primevue/paginator";
import { useI18n } from "vue-i18n";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import type { ExpiryAlertCollection, ExpiryAlertItem } from "@/Types/stock-alert-types";

defineProps<{
  alerts: ExpiryAlertCollection;
}>();

const emit = defineEmits<{
  page: [page: number, perPage: number];
}>();

const { t } = useI18n();
const { formatDateOnly, parseDateOnly } = useDatetimeFormatter();

function daysRemaining(item: ExpiryAlertItem): number {
  if (!item.expiry_date) return 0;
  const expiry = parseDateOnly(item.expiry_date);
  if (!expiry) return 0;
  const now = new Date();
  const expiryDay = Date.UTC(expiry.getFullYear(), expiry.getMonth(), expiry.getDate());
  const today = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());
  return Math.round((expiryDay - today) / (1000 * 60 * 60 * 24));
}

function expirySeverity(item: ExpiryAlertItem): "danger" | "warn" {
  return daysRemaining(item) <= 7 ? "danger" : "warn";
}

function expiryLabel(item: ExpiryAlertItem): string {
  const days = daysRemaining(item);
  if (days <= 0) return t("Expired");
  if (days === 1) return t("1 day");
  return t(":days days", { days });
}

function expiryDate(item: ExpiryAlertItem): string {
  return item.expiry_date ? formatDateOnly(item.expiry_date) : "—";
}

function onPage(event: PageState) {
  emit("page", event.page + 1, event.rows);
}
</script>

<template>
  <div>
    <div class="hidden md:block">
      <DataTable
        :value="alerts.data"
        data-key="id"
        lazy
        striped-rows
        paginator
        :total-records="alerts.meta.total"
        :rows="alerts.meta.per_page"
        :first="(alerts.meta.current_page - 1) * alerts.meta.per_page"
        :rows-per-page-options="[10, 20, 50]"
        :page-link-size="3"
        @page="onPage"
      >
        <template #empty>
          <div class="flex flex-col items-center gap-2 py-10 text-surface-500 dark:text-surface-400">
            <i class="fa fa-circle-check text-2xl text-green-600 dark:text-green-400" aria-hidden="true" />
            <span>{{ t("No expiry alerts") }}</span>
          </div>
        </template>

        <Column :header="t('Product')">
          <template #body="{ data }: { data: ExpiryAlertItem }">
            <div class="min-w-0">
              <p class="m-0 font-medium text-surface-900 dark:text-surface-0">{{ data.product_variant.product.name ?? "—" }}</p>
              <p
                v-if="data.product_variant.name || data.product_variant.identifier"
                class="m-0 mt-0.5 text-sm text-surface-500 dark:text-surface-400"
              >
                {{ [data.product_variant.name, data.product_variant.identifier].filter(Boolean).join(" · ") }}
              </p>
              <p class="m-0 mt-1 text-xs font-medium text-surface-600 dark:text-surface-300">
                {{ data.batch_identifier ? t("Batch: {identifier}", { identifier: data.batch_identifier }) : t("Batch not identified") }}
              </p>
            </div>
          </template>
        </Column>

        <Column :header="t('Store')" style="width: 180px">
          <template #body="{ data }: { data: ExpiryAlertItem }">
            <Chip v-if="data.store?.name" :label="data.store.code ? `${data.store.name} · ${data.store.code}` : data.store.name" />
            <span v-else>—</span>
          </template>
        </Column>

        <Column :header="t('Remaining')" style="width: 120px">
          <template #body="{ data }: { data: ExpiryAlertItem }">
            <span class="font-semibold text-surface-900 dark:text-surface-0">{{ data.remaining_quantity }}</span>
          </template>
        </Column>

        <Column :header="t('Expiry Date')" style="width: 150px">
          <template #body="{ data }: { data: ExpiryAlertItem }">
            {{ expiryDate(data) }}
          </template>
        </Column>

        <Column :header="t('Status')" style="width: 140px">
          <template #body="{ data }: { data: ExpiryAlertItem }">
            <Tag :value="expiryLabel(data)" :severity="expirySeverity(data)" />
          </template>
        </Column>
      </DataTable>
    </div>

    <div class="md:hidden">
      <ul v-if="alerts.data.length" class="m-0 flex list-none flex-col divide-y divide-surface-200 p-0 dark:divide-surface-700">
        <li v-for="item in alerts.data" :key="item.id" class="p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="m-0 font-semibold text-surface-900 dark:text-surface-0">{{ item.product_variant.product.name ?? "—" }}</p>
              <p
                v-if="item.product_variant.name || item.product_variant.identifier"
                class="m-0 mt-0.5 text-sm text-surface-500 dark:text-surface-400"
              >
                {{ [item.product_variant.name, item.product_variant.identifier].filter(Boolean).join(" · ") }}
              </p>
            </div>
            <Tag :value="expiryLabel(item)" :severity="expirySeverity(item)" />
          </div>

          <dl class="m-0 mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div>
              <dt class="text-surface-500 dark:text-surface-400">{{ t("Batch") }}</dt>
              <dd class="m-0 mt-0.5 font-medium text-surface-900 dark:text-surface-0">
                {{ item.batch_identifier ?? t("Not identified") }}
              </dd>
            </div>
            <div>
              <dt class="text-surface-500 dark:text-surface-400">{{ t("Remaining") }}</dt>
              <dd class="m-0 mt-0.5 font-medium text-surface-900 dark:text-surface-0">{{ item.remaining_quantity }}</dd>
            </div>
            <div>
              <dt class="text-surface-500 dark:text-surface-400">{{ t("Store") }}</dt>
              <dd class="m-0 mt-0.5 font-medium text-surface-900 dark:text-surface-0">{{ item.store?.name ?? "—" }}</dd>
            </div>
            <div>
              <dt class="text-surface-500 dark:text-surface-400">{{ t("Expiry Date") }}</dt>
              <dd class="m-0 mt-0.5 font-medium text-surface-900 dark:text-surface-0">{{ expiryDate(item) }}</dd>
            </div>
          </dl>
        </li>
      </ul>
      <div v-else class="flex flex-col items-center gap-2 px-4 py-10 text-center text-surface-500 dark:text-surface-400">
        <i class="fa fa-circle-check text-2xl text-green-600 dark:text-green-400" aria-hidden="true" />
        <span>{{ t("No expiry alerts") }}</span>
      </div>
      <Paginator
        v-if="alerts.meta.total > alerts.meta.per_page"
        :rows="alerts.meta.per_page"
        :first="(alerts.meta.current_page - 1) * alerts.meta.per_page"
        :total-records="alerts.meta.total"
        :rows-per-page-options="[10, 20, 50]"
        @page="onPage"
      />
    </div>
  </div>
</template>
