import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { FilterProps } from '@/types/base';

const PARAM = 'filter[trashed]';

/**
 * Drives spatie/query-builder's trashed filter: unset = active rows only,
 * `with` = active and deleted, `only` = deleted only.
 */
export default function TrashedFilter({
    updateParams,
    currentParams,
}: FilterProps) {
    const value = (currentParams?.[PARAM] as string) ?? 'active';

    return (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor="trashed-filter">Deleted records</Label>
            <Select
                value={value}
                onValueChange={(next) =>
                    updateParams?.({
                        [PARAM]: next === 'active' ? undefined : next,
                    })
                }
            >
                <SelectTrigger id="trashed-filter" className="w-48">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="active">Hide deleted</SelectItem>
                    <SelectItem value="with">Include deleted</SelectItem>
                    <SelectItem value="only">Deleted only</SelectItem>
                </SelectContent>
            </Select>
        </div>
    );
}
