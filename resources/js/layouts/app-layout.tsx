import { Link, usePage } from '@inertiajs/react';
import {
    ChevronsUpDown,
    KeyRound,
    LayoutDashboard,
    LogOut,
    ScrollText,
    Settings,
    ShieldCheck,
    UserCog,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';
import { usePermission } from '@/hooks/use-permission';
import { logout } from '@/routes';
import backoffice from '@/routes/backoffice';
import { edit as editProfile } from '@/routes/profile';
import type { SharedData, AppLayoutProps } from '@/types';

function getInitials(name: string) {
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function SidebarUser() {
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const user = usePage<SharedData>().props.auth.user;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent"
                        >
                            <Avatar className="h-8 w-8 rounded-full">
                                <AvatarFallback className="rounded-full bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {getInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">
                                    {user.name}
                                </span>
                                <span className="truncate text-xs text-muted-foreground">
                                    {user.email}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'left'
                                  : 'bottom'
                        }
                    >
                        <DropdownMenuLabel className="p-0 font-normal">
                            <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <Avatar className="h-8 w-8 rounded-full">
                                    <AvatarFallback className="rounded-full bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {getInitials(user.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-medium">
                                        {user.name}
                                    </span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {user.email}
                                    </span>
                                </div>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link
                                className="block w-full cursor-pointer"
                                href={editProfile()}
                            >
                                <UserCog className="mr-2" />
                                Account settings
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link
                                className="block w-full cursor-pointer"
                                href={logout()}
                                as="button"
                            >
                                <LogOut className="mr-2" />
                                Log out
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}

export default function AppLayout({ children }: AppLayoutProps) {
    const page = usePage<SharedData>();
    const { sidebarOpen: isOpen } = page.props;
    const currentUrl = page.url;
    const { can } = usePermission();

    function isMenuActive(href: string) {
        return currentUrl === href || currentUrl.startsWith(href + '/');
    }

    // Mirrors the `permission:` middleware on each route: no link is shown
    // that would only lead to a 403.
    const menuItems = [
        {
            title: 'Dashboard',
            href: backoffice.index.url(),
            icon: LayoutDashboard,
            visible: true,
        },
        {
            title: 'Users',
            href: backoffice.setting.user.index.url(),
            icon: Users,
            visible: can('user.view'),
        },
        {
            title: 'Roles',
            href: backoffice.setting.role.index.url(),
            icon: ShieldCheck,
            visible: can('role.view'),
        },
        {
            title: 'Permissions',
            href: backoffice.setting.permission.index.url(),
            icon: KeyRound,
            visible: can('permission.view'),
        },
        {
            title: 'Settings',
            href: backoffice.setting.setting.index.url(),
            icon: Settings,
            visible: can('setting.view'),
        },
        {
            title: 'Activity Log',
            href: backoffice.setting.activity.index.url(),
            icon: ScrollText,
            visible: can('activity.view'),
        },
    ].filter((item) => item.visible);

    return (
        <SidebarProvider defaultOpen={isOpen}>
            <Sidebar collapsible="icon" variant="sidebar">
                <SidebarHeader>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" asChild>
                                <Link href={backoffice.index.url()} prefetch>
                                    <AppLogo />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>
                <SidebarContent>
                    <SidebarGroup>
                        <SidebarGroupLabel>Menu</SidebarGroupLabel>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {menuItems.map((item) => (
                                    <SidebarMenuItem key={item.href}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={isMenuActive(item.href)}
                                        >
                                            <Link href={item.href}>
                                                <item.icon />
                                                <span>{item.title}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                </SidebarContent>
                <SidebarFooter>
                    <SidebarUser />
                </SidebarFooter>
            </Sidebar>
            <SidebarInset className="overflow-x-hidden">
                <header className="flex shrink-0 items-center gap-2 border-b px-4 py-2.5 sm:px-6 sm:py-3">
                    <SidebarTrigger />
                    <Separator orientation="vertical" className="mx-1 h-5" />
                    <h1 className="text-sm font-semibold tracking-tight sm:text-base">
                        {page.props.name}
                    </h1>
                </header>
                <div className="flex flex-1 flex-col gap-4 p-3 sm:p-4 md:p-6">
                    {children}
                </div>
            </SidebarInset>
        </SidebarProvider>
    );
}
