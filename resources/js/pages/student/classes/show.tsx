import { Head } from '@inertiajs/react';
import { CalendarDays, Clock, Info, User, Users } from 'lucide-react';
import CheckInButton from '@/components/check-in-button';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { index as classesIndex, show } from '@/routes/classes';
import type { ClassCard, PlanSummary } from '@/types';

type Props = {
    classSession: ClassCard;
    plan: PlanSummary;
};

export default function StudentClassShow({ classSession, plan }: Props) {
    return (
        <>
            <Head title={classSession.name} />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <header className="space-y-2">
                    <h1 className="text-2xl font-bold">{classSession.name}</h1>
                    <Badge
                        variant={classSession.is_full ? 'secondary' : 'default'}
                    >
                        {classSession.is_full
                            ? 'Aula lotada'
                            : `${classSession.available_spots} de ${classSession.max_students} vagas`}
                    </Badge>
                </header>

                <dl className="space-y-3 rounded-xl border bg-card p-4">
                    <div className="flex items-center gap-3">
                        <CalendarDays className="size-5 shrink-0 text-muted-foreground" />
                        <div>
                            <dt className="text-sm text-muted-foreground">
                                Quando
                            </dt>
                            <dd className="font-medium">
                                {classSession.weekday}, {classSession.date}
                            </dd>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Clock className="size-5 shrink-0 text-muted-foreground" />
                        <div>
                            <dt className="text-sm text-muted-foreground">
                                Horário
                            </dt>
                            <dd className="font-medium">
                                {classSession.starts_at} às{' '}
                                {classSession.ends_at}
                            </dd>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <User className="size-5 shrink-0 text-muted-foreground" />
                        <div>
                            <dt className="text-sm text-muted-foreground">
                                Professor
                            </dt>
                            <dd className="font-medium">
                                {classSession.teacher}
                            </dd>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Users className="size-5 shrink-0 text-muted-foreground" />
                        <div>
                            <dt className="text-sm text-muted-foreground">
                                Vagas
                            </dt>
                            <dd className="font-medium">
                                {classSession.available_spots} disponíveis
                            </dd>
                        </div>
                    </div>

                    {classSession.notes && (
                        <div className="flex items-center gap-3">
                            <Info className="size-5 shrink-0 text-muted-foreground" />
                            <div>
                                <dt className="text-sm text-muted-foreground">
                                    Observações
                                </dt>
                                <dd className="font-medium">
                                    {classSession.notes}
                                </dd>
                            </div>
                        </div>
                    )}
                </dl>

                {!plan.is_unlimited && plan.has_plan && (
                    <p className="text-center text-sm text-muted-foreground">
                        Você tem {plan.available_check_ins}{' '}
                        {plan.available_check_ins === 1
                            ? 'check-in disponível'
                            : 'check-ins disponíveis'}{' '}
                        nesta semana.
                    </p>
                )}

                <CheckInButton classCard={classSession} />
            </div>
        </>
    );
}

StudentClassShow.layout = {
    breadcrumbs: [
        { title: 'Início', href: dashboard() },
        { title: 'Aulas', href: classesIndex() },
        { title: 'Detalhes', href: show({ classSession: 0 }) },
    ],
};
