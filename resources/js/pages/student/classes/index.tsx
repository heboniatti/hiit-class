import { Head } from '@inertiajs/react';
import { CalendarDays, CircleAlert } from 'lucide-react';
import ClassCard from '@/components/class-card';
import EmptyState from '@/components/empty-state';
import { dashboard } from '@/routes';
import { index as classesIndex } from '@/routes/classes';
import type { ClassCard as ClassCardType, PlanSummary } from '@/types';

type Props = {
    classes: ClassCardType[];
    plan: PlanSummary;
};

export default function StudentClasses({ classes, plan }: Props) {
    return (
        <>
            <Head title="Aulas" />

            <div className="mx-auto w-full max-w-2xl space-y-4 p-4">
                <header>
                    <h1 className="text-2xl font-bold">Próximas aulas</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Toque em uma aula para fazer seu check-in.
                    </p>
                </header>

                {plan.payment_is_blocking && (
                    <p className="flex items-start gap-2 rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
                        <CircleAlert className="mt-0.5 size-4 shrink-0" />
                        Seu plano está temporariamente bloqueado por pagamento
                        pendente. Procure o professor para regularizar sua
                        mensalidade.
                    </p>
                )}

                {classes.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title="Nenhuma aula disponível"
                        description="Assim que o professor abrir novas aulas, elas aparecem aqui."
                    />
                ) : (
                    <ul className="space-y-3">
                        {classes.map((classCard) => (
                            <li key={classCard.id}>
                                <ClassCard classCard={classCard} />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

StudentClasses.layout = {
    breadcrumbs: [
        { title: 'Início', href: dashboard() },
        { title: 'Aulas', href: classesIndex() },
    ],
};
