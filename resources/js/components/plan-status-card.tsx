import { Link } from '@inertiajs/react';
import { CircleAlert, Infinity as InfinityIcon, Ticket } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { myPlan } from '@/routes';
import type { PlanSummary } from '@/types';

/**
 * The student's plan at a glance: what they have left this week, and whether
 * anything is blocking them.
 */
export default function PlanStatusCard({ plan }: { plan: PlanSummary }) {
    if (!plan.has_plan) {
        return (
            <div className="rounded-xl border border-dashed p-4">
                <p className="font-medium">Você ainda não tem um plano</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    Procure o professor para escolher o seu.
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-4 rounded-xl border bg-card p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-sm text-muted-foreground">Seu plano</p>
                    <p className="text-base font-semibold">{plan.plan_name}</p>
                </div>
                <Badge
                    variant={
                        plan.payment_is_blocking ? 'destructive' : 'secondary'
                    }
                >
                    {plan.payment_status}
                </Badge>
            </div>

            <div className="flex items-center gap-3 rounded-lg bg-primary/10 p-3 text-primary">
                {plan.is_unlimited ? (
                    <InfinityIcon className="size-6 shrink-0" />
                ) : (
                    <Ticket className="size-6 shrink-0" />
                )}
                <div>
                    <p className="text-2xl leading-none font-bold">
                        {plan.is_unlimited
                            ? 'Ilimitado'
                            : plan.available_check_ins}
                    </p>
                    <p className="mt-1 text-sm">
                        {plan.is_unlimited
                            ? 'Treine quantas vezes quiser'
                            : 'check-ins disponíveis nesta semana'}
                    </p>
                </div>
            </div>

            {plan.is_trial_offer && (
                <p className="rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                    Use este check-in em qualquer aula disponível. O plano
                    experimental será ativado quando você reservar sua vaga.
                </p>
            )}

            {plan.payment_is_blocking && (
                <p className="flex items-start gap-2 rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
                    <CircleAlert className="mt-0.5 size-4 shrink-0" />
                    Procure o professor para regularizar sua mensalidade.
                </p>
            )}

            <Link
                href={myPlan()}
                className="block text-sm text-primary underline-offset-4 hover:underline"
            >
                Ver plano e pagamentos
            </Link>
        </div>
    );
}
