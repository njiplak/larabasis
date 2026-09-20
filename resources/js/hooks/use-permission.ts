import { usePage } from '@inertiajs/react';

import type { SharedData } from '@/types';

/**
 * Mirrors the `permission:` route middleware. This only hides controls the
 * user cannot use; the server is still the thing that enforces access.
 */
export function usePermission() {
    const { permissions } = usePage<SharedData>().props.auth;

    const can = (permission: string): boolean => permissions.includes(permission);

    const canAny = (...candidates: string[]): boolean =>
        candidates.some((permission) => can(permission));

    return { permissions, can, canAny };
}
