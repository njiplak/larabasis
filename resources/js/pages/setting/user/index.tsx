import { usePage } from '@inertiajs/react';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';

import IndexPage from '@/components/index-page';
import ResetTwoFactorAction from '@/components/reset-two-factor-action';
import RestoreAction from '@/components/restore-action';
import TrashedFilter from '@/components/trashed-filter';
import AppLayout from '@/layouts/app-layout';
import { createDateColumn } from '@/lib/column-helpers';
import {
    create,
    destroy as destroyRoute,
    destroyBulk,
    fetch as fetchRoute,
    resetTwoFactor,
    restore,
    show,
} from '@/routes/backoffice/setting/user';
import type { SharedData } from '@/types';
import type { User } from '@/types/auth';
import type { Role } from '@/types/role';

type UserWithRole = User & {
    roles?: Role[];
};

const helper = createColumnHelper<UserWithRole>();

const columns: ColumnDef<UserWithRole, any>[] = [
    helper.accessor('id', {
        id: 'id',
        header: 'ID',
        enableColumnFilter: false,
        enableHiding: false,
    }),
    helper.accessor('name', {
        id: 'name',
        header: 'Name',
        enableColumnFilter: false,
        enableHiding: false,
    }),
    helper.accessor('email', {
        id: 'email',
        header: 'Email',
        enableColumnFilter: false,
        enableHiding: false,
    }),
    helper.display({
        id: 'role',
        header: 'Role',
        enableColumnFilter: false,
        enableHiding: false,
        cell: (ctx) => {
            const role = ctx.row.original.roles?.[0];
            if (!role) return <span className="text-muted-foreground">-</span>;
            return (
                <span className="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-700/10 ring-inset">
                    {role.name}
                </span>
            );
        },
    }),
    createDateColumn<UserWithRole>('created_at'),
    helper.display({
        id: 'two_factor',
        header: '2FA',
        enableColumnFilter: false,
        cell: (ctx) =>
            ctx.row.original.two_factor_enabled ? (
                <span className="text-xs font-medium text-emerald-600">On</span>
            ) : (
                <span className="text-xs text-muted-foreground">Off</span>
            ),
    }),
    helper.display({
        id: 'status',
        header: 'Status',
        enableColumnFilter: false,
        cell: (ctx) =>
            ctx.row.original.deleted_at ? (
                <span className="text-xs font-medium text-destructive">
                    Deleted
                </span>
            ) : (
                <span className="text-xs text-muted-foreground">Active</span>
            ),
    }),
];

const routes = {
    fetch: fetchRoute,
    destroy: destroyRoute,
    destroyBulk,
    show,
    create,
};

export default function UserIndex() {
    const { features } = usePage<SharedData>().props;

    return (
        <IndexPage<UserWithRole>
            title="User Management"
            description="Manage users and their role assignments"
            addLabel="Add User"
            columns={columns}
            module="user"
            routes={routes}
            filterComponent={<TrashedFilter />}
            actionExtras={(row) =>
                row.deleted_at ? (
                    <RestoreAction url={restore(row.id).url} />
                ) : null
            }
        />
    );
}

UserIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
