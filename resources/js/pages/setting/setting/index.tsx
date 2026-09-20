import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';

import IndexPage from '@/components/index-page';
import RestoreAction from '@/components/restore-action';
import TrashedFilter from '@/components/trashed-filter';
import AppLayout from '@/layouts/app-layout';
import { createDateColumn } from '@/lib/column-helpers';
import {
    create,
    destroy as destroyRoute,
    destroyBulk,
    fetch as fetchRoute,
    restore,
    show,
} from '@/routes/backoffice/setting/setting';
import type { Setting } from '@/types/setting';

const helper = createColumnHelper<Setting>();

const columns: ColumnDef<Setting, any>[] = [
    helper.accessor('id', {
        id: 'id',
        header: 'ID',
        enableColumnFilter: false,
        enableHiding: false,
    }),
    helper.accessor('key', {
        id: 'key',
        header: 'Key',
        enableColumnFilter: false,
        enableHiding: false,
    }),
    helper.accessor('value', {
        id: 'value',
        header: 'Value',
        enableColumnFilter: false,
        enableHiding: false,
    }),
    createDateColumn<Setting>('created_at'),
    helper.display({
        id: 'status',
        header: 'Status',
        enableColumnFilter: false,
        cell: (ctx) =>
            ctx.row.original.deleted_at ? (
                <span className="text-xs font-medium text-destructive">Deleted</span>
            ) : (
                <span className="text-xs text-muted-foreground">Active</span>
            ),
    }),
];

const routes = { fetch: fetchRoute, destroy: destroyRoute, destroyBulk, show, create };

export default function SettingIndex() {
    return (
        <IndexPage<Setting>
            title="Setting Management"
            description="Manage your application settings"
            addLabel="Add Setting"
            columns={columns}
            module="setting"
            routes={routes}
            filterComponent={<TrashedFilter />}
            actionExtras={(row) =>
                row.deleted_at ? <RestoreAction url={restore(row.id).url} /> : null
            }
        />
    );
}

SettingIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
