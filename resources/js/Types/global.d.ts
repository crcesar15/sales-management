import { SettingGrouped } from './setting-types';
import { User } from './user-types';

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