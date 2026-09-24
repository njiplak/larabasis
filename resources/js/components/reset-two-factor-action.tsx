import { router } from '@inertiajs/react';
import { ShieldOff } from 'lucide-react';

import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { createFormResponse } from '@/lib/constant';

/**
 * Clears a user's two-factor enrolment. The support path for someone who has
 * lost both their device and their recovery codes.
 */
export default function ResetTwoFactorAction({ url }: { url: string }) {
    return (
        <DropdownMenuItem
            onClick={(e) => {
                e.preventDefault();
                router.post(
                    url,
                    {},
                    createFormResponse(
                        'Two-factor reset. The user can enrol again.',
                    ),
                );
            }}
        >
            <ShieldOff />
            Reset 2FA
        </DropdownMenuItem>
    );
}
