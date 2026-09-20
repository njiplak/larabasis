import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { useCallback } from 'react';

import NextTable from '@/components/next-table';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { createDateColumn } from '@/lib/column-helpers';
import { fetch as fetchRoute } from '@/routes/backoffice/setting/activity';
import type { Activity } from '@/types/activity';
import type { Base } from '@/types/base';

const helper = createColumnHelper<Activity>();

const EVENT_VARIANT: Record<string, 'default' | 'secondary' | 'destructive'> = {
    created: 'default',
    updated: 'secondary',
    deleted: 'destructive',
    restored: 'default',
};

const columns: ColumnDef<Activity, any>[] = [
    helper.accessor('id', { id: 'id', header: 'ID', enableColumnFilter: false }),
    helper.display({
        id: 'event',
        header: 'Event',
        enableColumnFilter: false,
        cell: (ctx) => {
            const event = ctx.row.original.event ?? '-';
            return (
                <Badge variant={EVENT_VARIANT[event] ?? 'secondary'}>
                    {event}
                </Badge>
            );
        },
    }),
    helper.display({
        id: 'subject_type',
        header: 'Record',
        enableColumnFilter: false,
        cell: (ctx) => {
            const { subject_type: type, subject_id: id } = ctx.row.original;
            if (!type) return <span className="text-muted-foreground">-</span>;
            return (
                <span className="font-mono text-xs">
                    {type.split('\\').pop()}#{id}
                </span>
            );
        },
    }),
    helper.display({
        id: 'causer',
        header: 'By',
        enableColumnFilter: false,
        cell: (ctx) => {
            const causer = ctx.row.original.causer;
            if (!causer) return <span className="text-muted-foreground">system</span>;
            return (
                <div className="flex flex-col">
                    <span>{causer.name}</span>
                    <span className="text-xs text-muted-foreground">{causer.email}</span>
                </div>
            );
        },
    }),
    createDateColumn<Activity>('created_at', 'When'),
];

export default function ActivityIndex() {
    const load = useCallback(async (params: Record<string, any>) => {
        const response = await window.fetch(fetchRoute({ query: params }).url);
        return response.json() as Promise<Base<Activity[]>>;
    }, []);

    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-col">
                <h1 className="text-xl font-semibold">Activity Log</h1>
                <p className="hidden text-sm text-gray-500 sm:block">
                    Every create, update, delete and restore, and who did it
                </p>
            </div>
            <NextTable<Activity>
                enableSelect={false}
                load={load}
                id={'id' as keyof Activity}
                columns={columns}
                mode="table"
            />
        </div>
    );
}

ActivityIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
