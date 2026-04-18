<script setup lang="ts">
import AppLayout from "@layouts/admin.vue";

import { ref, watch, computed } from "vue";

import { DataTable, Card, Column, InputText, IconField, InputIcon, Tag, Select, type DataTablePageEvent, type DataTableSortEvent } from "primevue";

import { useI18n } from "vue-i18n";
import { useActivityLogClient } from "@composables/useActivityLogClient";
import type { ActivityLog } from "@app-types/activity-log-types";

// Layout
defineOptions({
  layout: AppLayout,
});

// Set composables
const { t } = useI18n();

// Pagination
const pagination = ref({
  total: 0,
  first: 0,
  page: 1,
  perPage: 15,
  sortField: "created_at",
  sortOrder: -1,
  filter: "",
});

const eventFilter = ref<string | null>(null);

const eventOptions = computed(() => [
  { label: t("All Events"), value: null },
  { label: t("Created"), value: "created" },
  { label: t("Updated"), value: "updated" },
  { label: t("Deleted"), value: "deleted" },
  { label: t("Restored"), value: "restored" },
]);

const { loading, fetchActivityLogsApi } = useActivityLogClient();

const activities = ref<ActivityLog[]>([]);

const eventSeverity = (event: string): string => {
  const map: Record<string, string> = {
    created: "success",
    updated: "info",
    deleted: "danger",
    restored: "warn",
  };
  return map[event] ?? "secondary";
};

const eventLabel = (event: string): string => {
  const map: Record<string, string> = {
    created: "Created",
    updated: "Updated",
    deleted: "Deleted",
    restored: "Restored",
  };
  return map[event] ?? event;
};

const formatFieldName = (key: string): string => {
  return key
    .split("_")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
};

const fetchActivityLogs = async () => {
  const params = new URLSearchParams();

  params.append("per_page", pagination.value.perPage.toString());
  params.append("page", pagination.value.page.toString());
  params.append("order_by", pagination.value.sortField);
  params.append("order_direction", pagination.value.sortOrder === -1 ? "desc" : "asc");

  if (pagination.value.filter) {
    params.append("filter", pagination.value.filter);
  }

  if (eventFilter.value) {
    params.append("event", eventFilter.value);
  }

  try {
    const response = await fetchActivityLogsApi(params.toString());
    activities.value = response.data.data;
    pagination.value.total = response.data.meta.total;
  } catch (_error) {
    activities.value = [];
  }
};

const onPage = (event: DataTablePageEvent) => {
  pagination.value.page = event.page + 1;
  pagination.value.perPage = event.rows;
  pagination.value.first = event.first;
  fetchActivityLogs();
};

const onSort = (event: DataTableSortEvent) => {
  pagination.value.sortField = typeof event.sortField === "string" ? event.sortField : "created_at";
  pagination.value.sortOrder = event.sortOrder ?? -1;
  fetchActivityLogs();
};

watch(
  () => pagination.value.filter,
  () => {
    pagination.value.page = 1;
    pagination.value.first = 0;
    fetchActivityLogs();
  },
  {
    immediate: true,
  },
);

watch(eventFilter, () => {
  pagination.value.page = 1;
  pagination.value.first = 0;
  fetchActivityLogs();
});
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Activity Log") }}
      </h2>
    </div>
    <Card>
      <template #content>
        <DataTable
          :value="activities"
          resizable-columns
          lazy
          :total-records="pagination.total"
          :rows="pagination.perPage"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="created_at"
          :sort-order="-1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ t("No activity found") }}
          </template>
          <template #header>
            <div class="grid grid-cols-12 gap-4">
              <div class="lg:col-span-3 md:col-span-4 col-span-12">
                <Select v-model="eventFilter" :options="eventOptions" option-label="label" option-value="value" class="w-full" />
              </div>
              <div class="flex lg:col-span-3 lg:col-start-10 md:col-span-4 md:col-start-9 col-span-12 md:justify-end justify-center">
                <IconField icon-position="left" class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText v-model="pagination.filter" :placeholder="t('Search')" class="w-full" />
                </IconField>
              </div>
            </div>
          </template>
          <Column field="event" :header="t('Event')">
            <template #body="{ data }">
              <Tag :severity="eventSeverity(data.event)">
                {{ t(eventLabel(data.event)) }}
              </Tag>
            </template>
          </Column>
          <Column field="subject_type" :header="t('Subject')">
            <template #body="{ data }">
              <span v-if="data.subject_type">{{ t(data.subject_type) }} #{{ data.subject_id }}</span>
              <span v-else>—</span>
            </template>
          </Column>
          <Column field="causer" :header="t('Caused By')">
            <template #body="{ data }">
              {{ data.causer?.full_name ?? t("System") }}
            </template>
          </Column>
          <Column field="properties" :header="t('Changes')">
            <template #body="{ data }">
              <div v-if="data.event === 'updated' && data.properties?.attributes">
                <div v-for="(value, key) in data.properties.attributes" :key="key" class="mb-1">
                  <span class="font-semibold">{{ t(formatFieldName(key as string)) }}:</span>
                  <span v-if="data.properties.old && data.properties.old[key] !== undefined" class="text-red-500 line-through mr-2">
                    {{ data.properties.old[key] }}
                  </span>
                  <span class="text-green-600">{{ value }}</span>
                </div>
              </div>
              <span v-else>—</span>
            </template>
          </Column>
          <Column field="created_at" :header="t('Date')" sortable />
        </DataTable>
      </template>
    </Card>
  </div>
</template>
