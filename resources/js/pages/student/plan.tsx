import { Form, Head } from '@inertiajs/react';
import { ExternalLink, Receipt, Upload } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import InputError from '@/components/input-error';
import PlanStatusCard from '@/components/plan-status-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { dashboard, myPlan } from '@/routes';
import paymentReceipts from '@/routes/payment-receipts';
import type { PaymentRow, PlanSummary } from '@/types';

type Props = {
    plan: PlanSummary;
    payments: PaymentRow[];
};

export default function StudentPlan({ plan, payments }: Props) {
    return (
        <>
            <Head title="Meu plano" />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <header>
                    <h1 className="text-2xl font-bold">Meu plano</h1>
                    {plan.has_plan && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {plan.allowance}
                            {plan.started_at && ` · desde ${plan.started_at}`}
                        </p>
                    )}
                </header>

                <PlanStatusCard plan={plan} />

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">Mensalidades</h2>

                    {payments.length === 0 ? (
                        <EmptyState
                            icon={Receipt}
                            title="Nenhuma mensalidade registrada"
                            description="Quando o professor registrar sua mensalidade, ela aparece aqui."
                        />
                    ) : (
                        <ul className="space-y-3">
                            {payments.map((payment) => (
                                <li
                                    key={payment.id}
                                    className="space-y-4 rounded-xl border bg-card p-4"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                R$ {payment.amount}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {payment.paid_at
                                                    ? `Pago em ${payment.paid_at}`
                                                    : `Vence em ${payment.due_date}`}
                                            </p>
                                        </div>
                                        <Badge
                                            variant={
                                                payment.is_overdue
                                                    ? 'destructive'
                                                    : 'secondary'
                                            }
                                            className="shrink-0"
                                        >
                                            {payment.status}
                                        </Badge>
                                    </div>

                                    {payment.has_receipt && (
                                        <div className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-muted/50 p-3 text-sm">
                                            <span className="text-muted-foreground">
                                                Comprovante enviado em{' '}
                                                {payment.receipt_uploaded_at}
                                            </span>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <a
                                                    href={paymentReceipts.show.url(
                                                        payment.id,
                                                    )}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    <ExternalLink className="size-4" />
                                                    Ver comprovante
                                                </a>
                                            </Button>
                                        </div>
                                    )}

                                    {!payment.is_settled && (
                                        <Form
                                            {...paymentReceipts.store.form(
                                                payment.id,
                                            )}
                                            options={{ preserveScroll: true }}
                                            resetOnSuccess
                                            className="space-y-2"
                                        >
                                            {({
                                                processing,
                                                progress,
                                                errors,
                                            }) => (
                                                <>
                                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                        <Input
                                                            name="receipt"
                                                            type="file"
                                                            accept=".pdf,.jpg,.jpeg,.png"
                                                            className="flex-1"
                                                            data-test={`receipt-input-${payment.id}`}
                                                        />
                                                        <Button
                                                            type="submit"
                                                            variant="secondary"
                                                            disabled={
                                                                processing
                                                            }
                                                            data-test={`receipt-submit-${payment.id}`}
                                                        >
                                                            {processing ? (
                                                                <Spinner />
                                                            ) : (
                                                                <Upload className="size-4" />
                                                            )}
                                                            {payment.has_receipt
                                                                ? 'Substituir'
                                                                : 'Enviar comprovante'}
                                                        </Button>
                                                    </div>
                                                    <p className="text-xs text-muted-foreground">
                                                        PDF, JPG ou PNG de até 5
                                                        MB. A mensalidade
                                                        aguarda a conferência do
                                                        professor.
                                                    </p>
                                                    {progress && (
                                                        <progress
                                                            value={
                                                                progress.percentage
                                                            }
                                                            max="100"
                                                            className="h-2 w-full"
                                                        >
                                                            {
                                                                progress.percentage
                                                            }
                                                            %
                                                        </progress>
                                                    )}
                                                    <InputError
                                                        message={errors.receipt}
                                                    />
                                                </>
                                            )}
                                        </Form>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}

                    <p className="text-sm text-muted-foreground">
                        Para mudar de plano, procure o professor. O comprovante
                        não dá baixa automaticamente.
                    </p>
                </section>
            </div>
        </>
    );
}

StudentPlan.layout = {
    breadcrumbs: [
        { title: 'Início', href: dashboard() },
        { title: 'Meu plano', href: myPlan() },
    ],
};
