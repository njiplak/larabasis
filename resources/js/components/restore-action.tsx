import { router } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';

import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { createFormResponse } from '@/lib/constant';

export default function RestoreAction({ url }: { url: string }) {
    return (
        <DropdownMenuItem
            onClick={(e) => {
                e.preventDefault();
                router.post(url, {}, createFormResponse('Record restored.'));
            }}
        >
            <RotateCcw />
            Restore
        </DropdownMenuItem>
    );
}
