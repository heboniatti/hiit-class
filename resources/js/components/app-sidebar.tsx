import { Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    ChartColumn,
    FileSpreadsheet,
    GraduationCap,
    History,
    LayoutGrid,
    Receipt,
    Settings,
    Target,
    Ticket,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, goals, history, myPlan } from '@/routes';
import { index as classesIndex } from '@/routes/classes';
import {
    dashboard as managementDashboard,
    overview,
    schedule,
} from '@/routes/management';
import managementGoals from '@/routes/management/goals';
import payments from '@/routes/management/payments';
import plans from '@/routes/management/plans';
import reports from '@/routes/management/reports';
import settings from '@/routes/management/settings';
import students from '@/routes/management/students';
import teachers from '@/routes/management/teachers';
import type { NavItem, UserRole } from '@/types';

/**
 * Build the navigation for the signed-in user's role.
 *
 * Students get their own area; teachers and admins get the management screens.
 * The last two are administrator-only — the routes enforce that as well, so
 * hiding them here is courtesy rather than security.
 */
function navItemsFor(role: UserRole): NavItem[] {
    if (role === 'student') {
        return [
            { title: 'Início', href: dashboard(), icon: LayoutGrid },
            { title: 'Aulas', href: classesIndex(), icon: CalendarDays },
            { title: 'Metas', href: goals(), icon: Target },
            { title: 'Histórico', href: history(), icon: History },
            { title: 'Meu plano', href: myPlan(), icon: Ticket },
        ];
    }

    const items: NavItem[] = [
        { title: 'Painel', href: managementDashboard(), icon: LayoutGrid },
        { title: 'Agenda', href: schedule(), icon: CalendarDays },
        { title: 'Alunos', href: students.index(), icon: Users },
        { title: 'Planos', href: plans.index(), icon: Ticket },
        { title: 'Pagamentos', href: payments.index(), icon: Receipt },
        { title: 'Metas', href: managementGoals.index(), icon: Target },
    ];

    if (role === 'admin') {
        items.push(
            {
                title: 'Professores',
                href: teachers.index(),
                icon: GraduationCap,
            },
            { title: 'Visão geral', href: overview(), icon: ChartColumn },
            {
                title: 'Relatórios',
                href: reports.index(),
                icon: FileSpreadsheet,
            },
            { title: 'Configurações', href: settings.edit(), icon: Settings },
        );
    }

    return items;
}

export function AppSidebar() {
    const role = usePage().props.auth.user.role;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItemsFor(role)} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
