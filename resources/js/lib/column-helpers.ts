import { Link } from '@inertiajs/react';
import {
    createColumnHelper,
    type CellContext,
    type ColumnDef,
} from '@tanstack/react-table';
import { Eye, Trash } from 'lucide-react';
import { createElement } from 'react';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export function createActionColumn<T extends { id: number | string }>(options: {
    showRoute: (id: number | string) => { url: string };
    setDeleteId: (id: any) => void;
    extraItems?: (row: T) => React.ReactNode;
    canUpdate?: boolean;
    canDelete?: boolean;
}): ColumnDef<T, any> {
    const helper = createColumnHelper<T>();

    return helper.display({
        id: 'action',
        header: 'Action',
        enableColumnFilter: false,
        enableHiding: false,
        cell: (ctx: CellContext<T, unknown>) => {
            const original = ctx.row.original;

            const canUpdate = options.canUpdate ?? true;
            const canDelete = options.canDelete ?? true;

            const detailItem = canUpdate
                ? createElement(
                      Link,
                      {
                          key: 'detail',
                          href: options.showRoute(original.id).url,
                          method: 'get',
                      } as any,
                      createElement(
                          DropdownMenuItem,
                          null,
                          createElement(Eye, null),
                          ' Detail',
                      ),
                  )
                : null;

            const deleteItem = canDelete
                ? createElement(
                      DropdownMenuItem,
                      {
                          key: 'delete',
                          className: 'text-red-500 hover:text-red-500',
                          onClick: (e: React.MouseEvent) => {
                              e.preventDefault();
                              options.setDeleteId(original.id);
                          },
                      },
                      createElement(Trash, { className: 'text-red-500' }),
                      ' ',
                      createElement(
                          'span',
                          { className: 'text-red-500' },
                          'Delete',
                      ),
                  )
                : null;

            const items = [
                detailItem,
                options.extraItems?.(original),
                deleteItem,
            ].filter(Boolean);

            if (items.length === 0) {
                return createElement(
                    'span',
                    { className: 'text-muted-foreground' },
                    '-',
                );
            }

            return createElement(
                DropdownMenu,
                null,
                createElement(
                    DropdownMenuTrigger,
                    { asChild: true },
                    createElement(
                        Button,
                        { variant: 'outline', size: 'sm' },
                        'Action',
                    ),
                ),
                createElement(
                    DropdownMenuContent,
                    { align: 'center' },
                    ...items,
                ),
            );
        },
    });
}

export function createDateColumn<T>(
    field: string,
    header = 'Created At',
): ColumnDef<T, any> {
    const helper = createColumnHelper<T>();

    return helper.display({
        id: field,
        header,
        enableColumnFilter: false,
        enableHiding: false,
        cell: (ctx) =>
            new Date((ctx.row.original as any)[field]).toLocaleDateString(
                'en-GB',
                { day: '2-digit', month: 'short', year: 'numeric' },
            ),
    });
}
