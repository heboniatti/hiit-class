import { Head } from '@inertiajs/react';
import {
    CalendarCheck,
    CircleAlert,
    ClipboardCheck,
    GraduationCap,
    Percent,
    Receipt,
    Ticket,
    Users,
} from 'lucide-react';
import GoalProgress from '@/components/goal-progress';
import StatCard from '@/components/stat-card';
import { dashboard } from '@/routes';

type Props = {
    period: string;
    totals: {
        active_students: number;
        teachers: number;
        classes_held: number;
        check_ins: number;
        active_plans: number;
        payments_pending: number;
        payments_overdue: number;
        attendance_rate: number;
    };
    monthlyAttendance: { label: string; value: number }[];
    collectiveGoals: {
        id: number;
        title: string;
        unit: string;
        ends_on: string;
        current: number;
        target: number;
        percent: number;
        is_complete: boolean;
    }[];
};

export default function Overview({
    period,
    totals,
    monthlyAttendance,
    collectiveGoals,
}: Props) {
    const peak = Math.max(...monthlyAttendance.map((m) => m.value), 1);

    return (
        <>
            <Head title="Visão geral" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <header>
                    <h1 className="text-2xl font-bold">Visão geral</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Últimos 30 dias · {period}
                    </p>
                </header>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        icon={Users}
                        label="Alunos ativos"
                        value={totals.active_students}
                    />
                    <StatCard
                        icon={GraduationCap}
                        label="Professores"
                        value={totals.teachers}
                    />
                    <StatCard
                        icon={CalendarCheck}
                        label="Aulas realizadas"
                        value={totals.classes_held}
                    />
                    <StatCard
                        icon={ClipboardCheck}
                        label="Check-ins"
                        value={totals.check_ins}
                    />
                    <StatCard
                        icon={Percent}
                        label="Taxa média de presença"
                        value={`${totals.attendance_rate}%`}
                    />
                    <StatCard
                        icon={Ticket}
                        label="Planos ativos"
                        value={totals.active_plans}
                    />
                    <StatCard
                        icon={Receipt}
                        label="Pagamentos pendentes"
                        value={totals.payments_pending}
                    />
                    <StatCard
                        icon={CircleAlert}
                        label="Pagamentos vencidos"
                        value={totals.payments_overdue}
                        tone="alert"
                    />
                </div>

                <section className="space-y-3 rounded-xl border bg-card p-4">
                    <h2 className="text-lg font-semibold">
                        Evolução mensal de frequência
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Presenças confirmadas por mês.
                    </p>

                    <ul className="flex items-end gap-3 pt-2">
                        {monthlyAttendance.map((month) => (
                            <li
                                key={month.label}
                                className="flex flex-1 flex-col items-center gap-2"
                            >
                                <span className="text-sm font-medium">
                                    {month.value}
                                </span>
                                <div
                                    className="w-full rounded-t bg-primary/70"
                                    style={{
                                        height: `${Math.max((month.value / peak) * 120, 4)}px`,
                                    }}
                                />
                                <span className="text-xs text-muted-foreground">
                                    {month.label}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>

                {collectiveGoals.length > 0 && (
                    <section className="space-y-3">
                        <h2 className="text-lg font-semibold">
                            Progresso das metas coletivas
                        </h2>
                        <ul className="space-y-3">
                            {collectiveGoals.map((goal) => (
                                <li
                                    key={goal.id}
                                    className="space-y-3 rounded-xl border bg-card p-4"
                                >
                                    <div>
                                        <p className="font-semibold">
                                            {goal.title}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            até {goal.ends_on}
                                        </p>
                                    </div>
                                    <GoalProgress
                                        current={goal.current}
                                        target={goal.target}
                                        percent={goal.percent}
                                        unit={goal.unit}
                                        isComplete={goal.is_complete}
                                    />
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}

Overview.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
