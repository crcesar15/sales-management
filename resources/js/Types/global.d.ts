import { SettingGrouped } from './setting-types';
import { User } from './user-types';
import type { DefineComponent } from 'vue';

// Extend the Inertia PageProps interface
declare module '@inertiajs/core' {
  interface PageProps {
    auth: {
      user: User;
      permissions: string[]
    };
    settings: SettingGrouped
  }
}

declare module '*.vue' {
  const component: DefineComponent<{}, {}, any>;
  export default component;
}