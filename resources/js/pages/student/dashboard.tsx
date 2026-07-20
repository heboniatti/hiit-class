import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Clock, Sparkles, User } from 'lucide-react';
import CheckInButton from '@/components/check-in-button';
import EmptyState from '@/components/empty-state';
import PlanStatusCard from '@/components/plan-status-card';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { index as classesIndex } from '@/routes/classes';
import type { ClassCard, PlanSummary, RecentAttendance } from '@/types';

type Props = {
    greeting: string;
    message: string;
    nextClass: ClassCard | null;
    plan: PlanSummary;
    attendance: RecentAttendance;
};

export default function StudentDashboard({
    greeting,
    message,
    nextClass,
    plan,
    attendance,
}: Props) {
    return (
        <>
            <Head title="Início" />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <header>
                    <h1 className="text-2xl font-bold">{greeting}</h1>
                    <p className="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                        <Sparkles className="size-4 shrink-0 text-primary" />
                        {message}
                    </p>
                </header>

                {/* The one main action of this screen. */}
                <section
                    aria-labelledby="next-class"
                    className="space-y-4 rounded-xl border bg-card p-5"
                >
                    <h2
                        id="next-class"
                        className="text-sm font-medium text-muted-foreground"
                    >
                        Sua próxima aula
                    </h2>

                    {nextClass ? (
                        <>
                            <div className="space-y-2">
                                <p className="text-xl font-bold">
                                    {nextClass.name}
                                </p>
                                <p className="flex items-center gap-2 text-sm">
                                    <CalendarDays className="size-4 text-muted-foreground" />
                                    {nextClass.weekday}, {nextClass.date}
                                </p>
                                <p className="flex items-center gap-2 text-sm">
                                    <Clock className="size-4 text-muted-foreground" />
                                    {nextClass.starts_at} às {nextClass.ends_at}
                                </p>
                                <p className="flex items-center gap-2 text-sm">
                                    <User className="size-4 text-muted-foreground" />
                                    {nextClass.teacher}
                                </p>
                                <Badge variant="secondary">
                                    {nextClass.available_spots} de{' '}
                                    {nextClass.max_students} vagas
                                </Badge>
                            </div>

                            <CheckInButton classCard={nextClass} />
                        </>
                    ) : (
                        <EmptyState
                            icon={CalendarDays}
                            title="Nenhuma aula agendada"
                            description="Assim que o professor abrir novas aulas, elas aparecem aqui."
                        />
                    )}
                </section>

                <PlanStatusCard plan={plan} />

                <section className="rounded-xl border bg-card p-4">
                    <p className="text-sm text-muted-foreground">
                        Sua frequência recente
                    </p>
                    <p className="mt-1 text-2xl font-bold">
                        {attendance.attended}{' '}
                        <span className="text-base font-normal text-muted-foreground">
                            {attendance.attended === 1 ? 'aula' : 'aulas'} nas
                            últimas {attendance.weeks} semanas
                        </span>
                    </p>
                </section>

                <Link
                    href={classesIndex()}
                    className="block rounded-xl border border-dashed p-4 text-center font-medium text-primary transition-colors hover:bg-primary/5"
                >
                    Ver todas as aulas
                </Link>
            </div>
        </>
    );
}

StudentDashboard.layout = {
    breadcrumbs: [{ title: 'Início', href: dashboard() }],
};
