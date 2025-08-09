export interface Permission {
  id: number;
  key: string;
  name: string;
  value: string | number | boolean | null;
  category: string;
}

export interface PermissionGrouped {
  value: string;
  category: string;
  permissions: Permissions[];
}

export type PermissionAccordion = Pick<Permission, 'id' | 'name'> & {enabled?: boolean}

export type PermissionGroupedAccordion = Pick<PermissionGrouped, 'value' | 'category'> & {permissions: PermissionAccordion[]}