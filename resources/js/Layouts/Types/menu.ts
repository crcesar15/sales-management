interface BaseMenuItem {
  label: string;
  icon: string;
  to?: string;
  route?: string;
  can?: string;
  url?: string;
  target?: string;
  class?: string;
  disabled?: boolean;
  visible?: boolean;
  separator?: boolean;
  command?: (event: { originalEvent: Event; item: MenuItemType }) => void;
}

export interface MenuItem extends BaseMenuItem {
  items?: MenuItemType[];
}

export interface MenuGroup extends BaseMenuItem {
  type: "single" | "multiple";
  items?: MenuItemType[];
}

export type MenuItemType = MenuItem | MenuGroup;

export interface UserAction {
  label: string;
  icon: string;
  command: () => void;
}

export interface AppMenuItemProps {
  item: MenuItemType;
  index: number;
  root?: boolean;
  parentItemKey?: string | null;
}
