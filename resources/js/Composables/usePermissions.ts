import { computed, ref } from 'vue';
import type { RawPermission, PermissionItem, PermissionCategoryRow } from '@/Types/permission-types';

export function usePermissions(
  rawPermissions: RawPermission[],
  initiallyEnabled: string[] = []
) {
  const matrix = ref<PermissionCategoryRow[]>(buildMatrix(rawPermissions, initiallyEnabled));

  // Search / filter
  const searchQuery = ref('');

  const filteredMatrix = computed(() => {
    if (!searchQuery.value) return matrix.value;
    const q = searchQuery.value.toLowerCase();
    return matrix.value.filter(row =>
      row.category.toLowerCase().includes(q) ||
      row.permissions.some(p => p.displayName.toLowerCase().includes(q) || p.name.toLowerCase().includes(q))
    );
  });

  // Enabled permission names (for form submission)
  const enabledPermissionNames = computed(() => {
    const names: string[] = [];
    matrix.value.forEach(row => {
      row.permissions.forEach(perm => {
        if (perm.enabled) names.push(perm.name);
      });
    });
    return names;
  });

  // Summary counts
  const totalPermissions = computed(() => {
    let count = 0;
    matrix.value.forEach(row => { count += row.permissions.length; });
    return count;
  });

  const totalEnabled = computed(() => enabledPermissionNames.value.length);

  // Toggle a single permission
  function togglePermission(category: string, permissionName: string) {
    const row = matrix.value.find(r => r.category === category);
    if (!row) return;
    const perm = row.permissions.find(p => p.name === permissionName);
    if (perm) perm.enabled = !perm.enabled;
  }

  // Toggle all in a category row
  function toggleCategoryAll(category: string, enabled: boolean) {
    const row = matrix.value.find(r => r.category === category);
    if (!row) return;
    row.permissions.forEach(perm => { perm.enabled = enabled; });
  }

  // Get category selection state: true (all), false (none), null (mixed)
  function isCategoryAllSelected(category: string): boolean | null {
    const row = matrix.value.find(r => r.category === category);
    if (!row || row.permissions.length === 0) return false;
    const enabledCount = row.permissions.filter(p => p.enabled).length;
    if (enabledCount === 0) return false;
    if (enabledCount === row.permissions.length) return true;
    return null;
  }

  // Count enabled permissions in a category
  function getCategoryEnabledCount(category: string): number {
    const row = matrix.value.find(r => r.category === category);
    if (!row) return 0;
    return row.permissions.filter(p => p.enabled).length;
  }

  // Toggle all globally
  function toggleAll(enabled: boolean) {
    matrix.value.forEach(row => {
      row.permissions.forEach(perm => { perm.enabled = enabled; });
    });
  }

  // Global selection state
  const isAllSelected = computed<boolean | null>(() => {
    const total = totalPermissions.value;
    if (total === 0) return false;
    const enabled = totalEnabled.value;
    if (enabled === 0) return false;
    if (enabled === total) return true;
    return null;
  });

  return {
    matrix,
    filteredMatrix,
    searchQuery,
    enabledPermissionNames,
    totalPermissions,
    totalEnabled,
    togglePermission,
    toggleCategoryAll,
    isCategoryAllSelected,
    getCategoryEnabledCount,
    toggleAll,
    isAllSelected,
  };
}

function buildMatrix(raw: RawPermission[], enabledNames: string[]): PermissionCategoryRow[] {
  const grouped = new Map<string, PermissionItem[]>();

  for (const p of raw) {
    const action = p.name.includes('.') ? p.name.split('.').slice(1).join('.') : p.name;

    if (!grouped.has(p.category)) {
      grouped.set(p.category, []);
    }

    grouped.get(p.category)!.push({
      id: p.id,
      name: p.name,
      displayName: action,
      enabled: enabledNames.includes(p.name),
    });
  }

  return Array.from(grouped.entries()).map(([category, permissions]) => ({
    category,
    permissions,
  }));
}
