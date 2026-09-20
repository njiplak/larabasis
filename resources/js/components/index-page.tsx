import { router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Plus } from 'lucide-react';
import type { ReactNode } from 'react';

import { DeleteDialog } from '@/components/delete-dialog';
import NextTable from '@/components/next-table';
import { Button } from '@/components/ui/button';
import { useCrudTable, type CrudRoutes } from '@/hooks/use-crud-table';
import { usePermission } from '@/hooks/use-permission';
import { createActionColumn } from '@/lib/column-helpers';

export type IndexPageProps<T extends { id: number | string }> = {
    title: string;
    description: string;
    addLabel?: string;
    columns: ColumnDef<T, any>[];
    routes: CrudRoutes<T>;
    hideAdd?: boolean;
    disableSelect?: boolean;
    showActionColumn?: boolean;
    actionExtras?: (row: T) => ReactNode;
    headerActions?: ReactNode;
    filterComponent?: ReactNode;
    tableProps?: Record<string, any>;
    /**
     * Module name, e.g. "user". When given, the Add button, the row actions
     * and bulk delete follow the same permissions as the routes behind them.
     */
    module?: string;
};

export default function IndexPage<T extends { id: number | string }>({
    title,
    description,
    addLabel = 'Add',
    columns,
    routes,
    hideAdd = false,
    disableSelect = false,
    showActionColumn = true,
    actionExtras,
    headerActions,
    filterComponent,
    tableProps,
    module,
}: IndexPageProps<T>) {
    const { can } = usePermission();
    const { deleteId, setDeleteId, setSelected, onDelete, onBulkDelete, load } =
        useCrudTable<T>(routes);

    const allow = (action: string) => !module || can(`${module}.${action}`);

    const allColumns: ColumnDef<T, any>[] = showActionColumn
        ? [
              ...columns,
              createActionColumn<T>({
                  showRoute: (id) => routes.show(id),
                  setDeleteId,
                  extraItems: actionExtras,
                  canUpdate: allow('update'),
                  canDelete: allow('delete'),
              }),
          ]
        : columns;

    return (
        <>
            <DeleteDialog id={deleteId} onDelete={onDelete} onOpenChange={setDeleteId} />

            <div className="flex flex-col gap-4">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex flex-col">
                        <h1 className="text-xl font-semibold">{title}</h1>
                        <p className="hidden text-sm text-gray-500 sm:block">{description}</p>
                    </div>
                    <div className="flex gap-2">
                        {headerActions}
                        {!hideAdd && allow('create') && (
                            <Button onClick={() => router.visit(routes.create().url)}>
                                <Plus className="size-4" />
                                <span className="hidden sm:inline">{addLabel}</span>
                            </Button>
                        )}
                    </div>
                </div>
                <NextTable<T>
                    enableSelect={!disableSelect}
                    onSelect={(select) => setSelected(select as any[])}
                    load={load}
                    onBulkDelete={allow('delete') ? onBulkDelete : undefined}
                    id={'id' as keyof T}
                    columns={allColumns}
                    mode="table"
                    filterComponent={filterComponent}
                    {...tableProps}
                />
            </div>
        </>
    );
}
