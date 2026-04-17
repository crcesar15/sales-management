<template>
  <Card>
    <template #content>
      <!-- Toolbar -->
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-3">
          <Checkbox
            :model-value="isAllSelected === true"
            :indeterminate="isAllSelected === null"
            binary
            input-id="global-select-all"
            @update:model-value="emit('toggle-all', $event)"
          />
          <label for="global-select-all" class="font-semibold uppercase text-sm cursor-pointer">{{ t("Select All") }}</label>
          <Badge
            :value="`${totalEnabled} / ${totalPermissions}`"
            :severity="totalEnabled === totalPermissions && totalPermissions > 0 ? 'primary' : 'secondary'"
          />
        </div>
        <IconField icon-position="left">
          <InputIcon class="fa fa-search" />
          <InputText
            :model-value="searchQuery"
            :placeholder="t('Filter...')"
            @update:model-value="emit('update:searchQuery', $event ?? '')"
          />
        </IconField>
      </div>

      <!-- Permission Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <tbody>
            <tr
              v-for="row in matrix"
              :key="row.category"
              class="border-b border-surface-200 dark:border-surface-700 hover:bg-surface-50 dark:hover:bg-surface-800 transition-colors duration-150"
            >
              <!-- Category cell -->
              <td class="py-3 pe-4 whitespace-nowrap align-center">
                <div class="flex items-center gap-2">
                  <Checkbox
                    :model-value="isCategoryAllSelected(row.category) === true"
                    :indeterminate="isCategoryAllSelected(row.category) === null"
                    binary
                    :input-id="`cat-${row.category}`"
                    @update:model-value="emit('toggle-category-all', row.category, $event)"
                  />
                  <label :for="`cat-${row.category}`" class="uppercase font-bold text-sm cursor-pointer">{{ t(row.category) }}</label>
                  <Badge
                    :value="getCategoryEnabledCount(row.category)"
                    :severity="
                      getCategoryEnabledCount(row.category) === row.permissions.length && row.permissions.length > 0
                        ? 'primary'
                        : 'secondary'
                    "
                  />
                </div>
              </td>
              <!-- Permissions cell -->
              <td class="py-3 ps-4">
                <div class="flex flex-wrap gap-x-5 gap-y-2">
                  <label
                    v-for="perm in row.permissions"
                    :key="perm.name"
                    :for="`perm-${perm.name}`"
                    class="flex items-center gap-2 cursor-pointer min-h-[44px]"
                  >
                    <Checkbox
                      :model-value="perm.enabled"
                      binary
                      :input-id="`perm-${perm.name}`"
                      @update:model-value="emit('toggle-permission', row.category, perm.name)"
                    />
                    <span class="text-sm first-letter:uppercase">{{ t(perm.displayName) }}</span>
                  </label>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </Card>
</template>

<script setup lang="ts">
import { Card, Checkbox, Badge, IconField, InputIcon, InputText } from "primevue";
import { useI18n } from "vue-i18n";
import type { PermissionCategoryRow } from "@/Types/permission-types";

const { t } = useI18n();

defineProps<{
  matrix: PermissionCategoryRow[];
  searchQuery: string;
  totalPermissions: number;
  totalEnabled: number;
  isAllSelected: boolean | null;
  isCategoryAllSelected: (category: string) => boolean | null;
  getCategoryEnabledCount: (category: string) => number;
}>();

const emit = defineEmits<{
  (e: "update:searchQuery", value: string): void;
  (e: "toggle-permission", category: string, permissionName: string): void;
  (e: "toggle-category-all", category: string, enabled: boolean): void;
  (e: "toggle-all", enabled: boolean): void;
}>();
</script>
