<script setup lang="ts">
import { Badge, Button, Column, DataTable, Paginator, Tag } from "primevue";
import type { PageState } from "primevue/paginator";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { LowStockAlertCollection, LowStockAlertItem } from "@/Types/stock-alert-types";

defineProps<{
  alerts: LowStockAlertCollection;
}>();

const emit = defineEmits<{
  page: [page: number, perPage: number];
}>();

const { t } = useI18n();

function severity(item: LowStockAlertItem): "danger" | "warn" {
  return item.total_stock === 0 ? "danger" : "warn";
}

function severityLabel(item: LowStockAlertItem): string {
  return item.total_stock === 0 ? t("Out of Stock") : t("Low Stock");
}

function onPage(event: PageState) {
  emit("page", event.page + 1, event.rows);
}

function viewVariant(item: LowStockAlertItem) {
  router.visit(route("inventory.variants.show", { variant: item.id }));
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
            <span>{{ t("No low stock alerts") }}</span>
          </div>
        </template>

        <Column :header="t('Product')">
          <template #body="{ data }: { data: LowStockAlertItem }">
            <div class="min-w-0">
              <p class="m-0 font-medium text-surface-900 dark:text-surface-0">{{ data.product_name ?? "—" }}</p>
              <div v-if="data.values.length" class="mt-1 flex flex-wrap gap-1">
                <Badge
                  v-for="value in data.values"
                  :key="value.id"
                  :value="value.option_name ? `${value.option_name}: ${value.value}` : value.value"
                  severity="secondary"
                />
              </div>
              <p v-if="data.brand_name || data.identifier" class="m-0 mt-1 text-sm text-surface-500 dark:text-surface-400">
                {{ [data.brand_name, data.identifier].filter(Boolean).join(" · ") }}
              </p>
            </div>
          </template>
        </Column>

        <Column :header="t('Stock Position')" style="width: 230px">
          <template #body="{ data }: { data: LowStockAlertItem }">
            <p class="m-0 font-medium text-surface-900 dark:text-surface-0">{{ t("{count} in stock", { count: data.total_stock }) }}</p>
            <p
              class="m-0 mt-1 text-sm"
              :class="data.total_stock === 0 ? 'text-red-600 dark:text-red-400' : 'text-orange-600 dark:text-orange-400'"
            >
              {{ t("{count} needed", { count: data.shortage_quantity }) }} · {{ t("Minimum {count}", { count: data.minimum_stock_level }) }}
            </p>
          </template>
        </Column>

        <Column :header="t('Status')" style="width: 140px">
          <template #body="{ data }: { data: LowStockAlertItem }">
            <Tag :value="severityLabel(data)" :severity="severity(data)" />
          </template>
        </Column>

        <Column :header="t('Actions')" style="width: 80px">
          <template #body="{ data }: { data: LowStockAlertItem }">
            <Button
              v-tooltip.top="t('Manage')"
              icon="fa-solid fa-eye"
              text
              rounded
              :aria-label="t('Manage {product}', { product: data.product_name ?? t('Product') })"
              @click="viewVariant(data)"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <div class="md:hidden">
      <ul v-if="alerts.data.length" class="m-0 flex list-none flex-col divide-y divide-surface-200 p-0 dark:divide-surface-700">
        <li v-for="item in alerts.data" :key="item.id" class="p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="m-0 font-semibold text-surface-900 dark:text-surface-0">{{ item.product_name ?? "—" }}</p>
              <p v-if="item.brand_name || item.identifier" class="m-0 mt-0.5 text-sm text-surface-500 dark:text-surface-400">
                {{ [item.brand_name, item.identifier].filter(Boolean).join(" · ") }}
              </p>
            </div>
            <Tag :value="severityLabel(item)" :severity="severity(item)" />
          </div>

          <div v-if="item.values.length" class="mt-2 flex flex-wrap gap-1">
            <Badge
              v-for="value in item.values"
              :key="value.id"
              :value="value.option_name ? `${value.option_name}: ${value.value}` : value.value"
              severity="secondary"
            />
          </div>

          <div class="mt-4 flex items-end justify-between gap-3">
            <div>
              <p class="m-0 font-medium text-surface-900 dark:text-surface-0">{{ t("{count} in stock", { count: item.total_stock }) }}</p>
              <p
                class="m-0 mt-0.5 text-sm"
                :class="item.total_stock === 0 ? 'text-red-600 dark:text-red-400' : 'text-orange-600 dark:text-orange-400'"
              >
                {{ t("{count} needed", { count: item.shortage_quantity }) }}
              </p>
            </div>
            <Button
              icon="fa-solid fa-eye"
              text
              rounded
              :aria-label="t('Manage {product}', { product: item.product_name ?? t('Product') })"
              @click="viewVariant(item)"
            />
          </div>
        </li>
      </ul>
      <div v-else class="flex flex-col items-center gap-2 px-4 py-10 text-center text-surface-500 dark:text-surface-400">
        <i class="fa fa-circle-check text-2xl text-green-600 dark:text-green-400" aria-hidden="true" />
        <span>{{ t("No low stock alerts") }}</span>
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
