import { PageProps as InertiaPageProps } from '@inertiajs/core';

export interface User {
  id: number;
  name: string;
  email: string;
}

export interface Permission {
  key: string;
  name: string;
  value: string | number | boolean | null;
}

// Extend the Inertia PageProps interface
declare module '@inertiajs/core' {
  interface PageProps {
    auth: {
      user: User;
      permissions: string[]
    };
    settings: Setting[]
    // Add any other global props here
  }
}