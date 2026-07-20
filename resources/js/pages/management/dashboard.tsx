import { Head, Link } from '@inertiajs/react';
import {
    CalendarDays,
    CircleAlert,
    ClipboardCheck,
    TicketX,
    TrendingDown,
    UserPlus,
} from 'lucide-react';
import EmptyState from '@/components/empty-state';
import StatCard from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import attendance from '@/routes/management/classes/attendance';
import students from '@/routes/management/students';

type ClassToday = {
    id: number;
    name: string;
    starts_at: string;
    ends_at: string;
    teacher: string;
    taken: number;
    max_students: number;
    available: number;
    status: string;
    is_cancelled: boolean;
};

type Props = {
    today: string;
    classesToday: ClassToday[];
    checkInsToday: number;
    awaitingAttendance: {
        id: number;
        name: string;
        date: string;
        starts_at: string;
        pending: number;
    }[];
    studentsWithoutPlan: {
        id: number;
        name: string;
        phone: string;
        registered_at: string;
    }[];
    studentsOverdue: {
        id: number;
        name: string;
        phone: string;
        due_date: string;
        amount: string;
    }[];
    lowFrequencyStudents: {
        id: number;
        name: string;
        phone: string;
        attended: number;
    }[];
};

export default function ManagementDashboard({
    today,
    classesToday,
    checkInsToday,
    awaitingAttendance,
    studentsWithoutPlan,
    studentsOverdue,
    lowFrequencyStudents,
}: Props) {
    return (
        <>
            <Head title="Painel" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <header>
                    <h1 className="text-2xl font-bold">Painel do dia</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {today}
                    </p>
                </header>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        icon={CalendarDays}
                        label="Aulas hoje"
                        value={classesToday.length}
                    />
                    <StatCard
                        icon={ClipboardCheck}
                        label="Check-ins hoje"
                        value={checkInsToday}
                    />
                    <StatCard
                        icon={UserPlus}
                        label="Alunos sem plano"
                        value={studentsWithoutPlan.length}
                        tone="alert"
                    />
                    <StatCard
                        icon={CircleAlert}
                        label="Pagamentos vencidos"
                        value={studentsOverdue.length}
                        tone="alert"
                    />
                </div>

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">Aulas de hoje</h2>

                    {classesToday.length === 0 ? (
                        <EmptyState
                            icon={CalendarDays}
                            title="Nenhuma aula hoje"
                            description="Não há aulas agendadas para hoje."
                        />
                    ) : (
                        <ul className="space-y-3">
                            {classesToday.map((classToday) => (
                                <li key={classToday.id}>
                                    <Link
                                        href={attendance.edit({
                                            classSession: classToday.id,
                                        })}
                                        className="flex items-center justify-between gap-3 rounded-xl border bg-card p-4 transition-colors hover:border-primary/50"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-semibold">
                                                {classToday.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {classToday.starts_at} às{' '}
                                                {classToday.ends_at} ·{' '}
                                                {classToday.teacher}
                                            </p>
                                        </div>
                                        <div className="shrink-0 text-right">
                                            {classToday.is_cancelled ? (
                                                <Badge variant="secondary">
                                                    {classToday.status}
                                                </Badge>
                                            ) : (
                                                <>
                                                    <p className="font-semibold">
                                                        {classToday.taken}/
                                                        {
                                                            classToday.max_students
                                                        }
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {classToday.available}{' '}
                                                        vagas
                                                    </p>
                                                </>
                                            )}
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {awaitingAttendance.length > 0 && (
                    <section className="space-y-3">
                        <h2 className="text-lg font-semibold">
                            Presenças aguardando confirmação
                        </h2>
                        <ul className="space-y-2">
                            {awaitingAttendance.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={attendance.edit({
                                            classSession: item.id,
                                        })}
                                        className="flex items-center justify-between gap-3 rounded-lg border bg-card p-3 text-sm transition-colors hover:border-primary/50"
                                    >
                                        <span>
                                            {item.name} · {item.date} às{' '}
                                            {item.starts_at}
                                        </span>
                                        <Badge variant="secondary">
                                            {item.pending} a confirmar
                                        </Badge>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="space-y-3">
                        <h2 className="text-lg font-semibold">
                            Alunos sem plano ativo
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Quem está com o acesso experimental disponível e
                            ainda precisa escolher um plano recorrente.
                        </p>

                        {studentsWithoutPlan.length === 0 ? (
                            <EmptyState
                                icon={UserPlus}
                                title="Ninguém esperando"
                                description="Todos os alunos ativos já têm um plano."
                            />
                        ) : (
                            <ul className="space-y-2">
                                {studentsWithoutPlan.map((item) => (
                                    <li key={item.id}>
                                        <Link
                                            href={students.show(item.id)}
                                            className="flex items-center justify-between gap-3 rounded-lg border bg-card p-3 text-sm transition-colors hover:border-primary/50"
                                        >
                                            <span className="truncate">
                                                {item.name}
                                            </span>
                                            <span className="shrink-0 text-muted-foreground">
                                                desde {item.registered_at}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-3">
                        <h2 className="text-lg font-semibold">
                            Pagamentos vencidos
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Estes alunos estão bloqueados para novos check-ins.
                        </p>

                        {studentsOverdue.length === 0 ? (
                            <EmptyState
                                icon={TicketX}
                                title="Nenhuma pendência"
                                description="Todas as mensalidades estão em dia."
                            />
                        ) : (
                            <ul className="space-y-2">
                                {studentsOverdue.map((item) => (
                                    <li key={item.id}>
                                        <Link
                                            href={students.show(item.id)}
                                            className="flex items-center justify-between gap-3 rounded-lg border bg-card p-3 text-sm transition-colors hover:border-primary/50"
                                        >
                                            <span className="truncate">
                                                {item.name}
                                            </span>
                                            <span className="shrink-0 text-destructive">
                                                R$ {item.amount} ·{' '}
                                                {item.due_date}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                {lowFrequencyStudents.length > 0 && (
                    <section className="space-y-3">
                        <h2 className="text-lg font-semibold">
                            Alunos com baixa frequência
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Um lembrete para você dar um alô. Isto não aparece
                            para os alunos.
                        </p>
                        <ul className="space-y-2">
                            {lowFrequencyStudents.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={students.show(item.id)}
                                        className="flex items-center justify-between gap-3 rounded-lg border bg-card p-3 text-sm transition-colors hover:border-primary/50"
                                    >
                                        <span className="flex items-center gap-2 truncate">
                                            <TrendingDown className="size-4 shrink-0 text-muted-foreground" />
                                            {item.name}
                                        </span>
                                        <span className="shrink-0 text-muted-foreground">
                                            {item.attended} aulas em 4 semanas
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}

ManagementDashboard.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
