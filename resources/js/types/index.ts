export type * from './auth';
export type * from './navigation';
export type * from './ui';

import type { Auth } from './auth';

export type Features = {
    twoFactor: boolean;
};

export type SharedData = {
    name: string;
    auth: Auth;
    features: Features;
    sidebarOpen: boolean;
    [key: string]: unknown;
};
