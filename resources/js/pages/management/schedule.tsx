import { Head, Link } from '@inertiajs/react';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Plus,
    Repeat,
} from 'lucide-react';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { schedule } from '@/routes/management';
import classes from '@/routes/management/classes';
import attendance from '@/routes/management/classes/attendance';

type ScheduledClass = {
    id: number;
    name: string;
    date: string;
    weekday: string;
    starts_at: string;
    ends_at: string;
    teacher: string;
    taken: number;
    max_students: number;
    status: string;
    is_cancelled: boolean;
    is_recurring: boolean;
};

type Props = {
    classes: ScheduledClass[];
    week: { offset: number; label: string; is_current: boolean };
};

export default function Schedule({ classes: rows, week }: Props) {
    return (
        <>
            <Head title="Agenda" />

            <div className="mx-auto w-full max-w-4xl space-y-4 p-4">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold">Agenda</h1>
                    <Button asChild>
                        <Link href={classes.create()}>
                            <Plus className="size-4" />
                            Nova aula
                        </Link>
                    </Button>
                </header>

                <div className="flex items-center justify-between gap-2 rounded-xl border bg-card p-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={schedule({
                                query: { week: week.offset - 1 },
                            })}
                        >
                            <ChevronLeft className="size-4" />
                            Anterior
                        </Link>
                    </Button>

                    <div className="text-center">
                        <p className="text-sm font-medium">{week.label}</p>
                        {week.is_current && (
                            <p className="text-xs text-muted-foreground">
                                Esta semana
                            </p>
                        )}
                    </div>

                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={schedule({
                                query: { week: week.offset + 1 },
                            })}
                        >
                            Próxima
                            <ChevronRight className="size-4" />
                        </Link>
                    </Button>
                </div>

                {rows.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title="Nenhuma aula nesta semana"
                        description="Crie uma aula avulsa ou uma aula que se repete toda semana."
                    />
                ) : (
                    <ul className="space-y-3">
                        {rows.map((row) => (
                            <li
                                key={row.id}
                                className="rounded-xl border bg-card p-4"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="flex items-center gap-2 font-semibold">
                                            <span className="truncate">
                                                {row.name}
                                            </span>
                                            {row.is_recurring && (
                                                <Repeat className="size-4 shrink-0 text-muted-foreground" />
                                            )}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {row.weekday}, {row.date} ·{' '}
                                            {row.starts_at} às {row.ends_at}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {row.teacher}
                                        </p>
                                    </div>
                                    <div className="shrink-0 text-right">
                                        {row.is_cancelled ? (
                                            <Badge variant="secondary">
                                                {row.status}
                                            </Badge>
                                        ) : (
                                            <p className="font-semibold">
                                                {row.taken}/{row.max_students}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="mt-3 flex gap-2">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        asChild
                                    >
                                        <Link
                                            href={attendance.edit({
                                                classSession: row.id,
                                            })}
                                        >
                                            Lista de presença
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link
                                            href={classes.edit({
                                                classSession: row.id,
                                            })}
                                        >
                                            Editar
                                        </Link>
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Schedule.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
