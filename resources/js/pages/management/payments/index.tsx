import { Form, Head, Link } from '@inertiajs/react';
import { CircleCheck, ExternalLink, Plus, Receipt } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import payments from '@/routes/management/payments';
import paymentReceipts from '@/routes/payment-receipts';

type PaymentRow = {
    id: number;
    student: string;
    student_id: number;
    amount: string;
    due_date: string;
    paid_at: string | null;
    method: string | null;
    status: string;
    is_overdue: boolean;
    is_settled: boolean;
    notes: string | null;
    is_automatic: boolean;
    has_receipt: boolean;
    receipt_uploaded_at: string | null;
    recorded_by: string | null;
};

type Option = { value: string; label: string };

type Props = {
    payments: PaymentRow[];
    filter: string;
    students: {
        value: number;
        label: string;
        due_day: number | null;
        price: number | null;
    }[];
    methods: Option[];
    statuses: Option[];
    counts: { overdue: number; pending: number };
};

const FILTERS = [
    { value: 'overdue', label: 'Vencidos' },
    { value: 'pending', label: 'Pendentes' },
    { value: 'paid', label: 'Pagos' },
    { value: 'all', label: 'Todos' },
];

export default function PaymentsIndex({
    payments: rows,
    filter,
    students,
    methods,
    statuses,
    counts,
}: Props) {
    const [settling, setSettling] = useState<PaymentRow | null>(null);
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title="Pagamentos" />

            <div className="mx-auto w-full max-w-3xl space-y-4 p-4">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold">Pagamentos</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {counts.overdue} vencido(s) · {counts.pending}{' '}
                            pendente(s)
                        </p>
                    </div>

                    <Dialog open={creating} onOpenChange={setCreating}>
                        <DialogTrigger asChild>
                            <Button data-test="new-payment-button">
                                <Plus className="size-4" />
                                Registrar mensalidade
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[90vh] overflow-y-auto">
                            <DialogTitle>Registrar mensalidade</DialogTitle>
                            <DialogDescription>
                                Fica registrado que foi você quem lançou.
                            </DialogDescription>

                            <Form
                                {...payments.store.form()}
                                options={{ preserveScroll: true }}
                                onSuccess={() => setCreating(false)}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="user_id">
                                                Aluno
                                            </Label>
                                            <Select name="user_id">
                                                <SelectTrigger id="user_id">
                                                    <SelectValue placeholder="Escolha o aluno" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {students.map((student) => (
                                                        <SelectItem
                                                            key={student.value}
                                                            value={String(
                                                                student.value,
                                                            )}
                                                        >
                                                            {student.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.user_id}
                                            />
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="amount">
                                                    Valor (R$)
                                                </Label>
                                                <Input
                                                    id="amount"
                                                    name="amount"
                                                    type="number"
                                                    inputMode="decimal"
                                                    step="0.01"
                                                    min={0}
                                                    required
                                                />
                                                <InputError
                                                    message={errors.amount}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="due_date">
                                                    Vencimento
                                                </Label>
                                                <Input
                                                    id="due_date"
                                                    name="due_date"
                                                    type="date"
                                                    required
                                                />
                                                <InputError
                                                    message={errors.due_date}
                                                />
                                            </div>
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="status">
                                                Situação
                                            </Label>
                                            <Select
                                                name="status"
                                                defaultValue="pending"
                                            >
                                                <SelectTrigger id="status">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {statuses.map((status) => (
                                                        <SelectItem
                                                            key={status.value}
                                                            value={status.value}
                                                        >
                                                            {status.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.status}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="notes">
                                                Observações{' '}
                                                <span className="text-muted-foreground">
                                                    (opcional)
                                                </span>
                                            </Label>
                                            <Input id="notes" name="notes" />
                                        </div>

                                        <DialogFooter>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                data-test="save-payment-button"
                                            >
                                                {processing && <Spinner />}
                                                Registrar
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </header>

                <div className="flex flex-wrap gap-2">
                    {FILTERS.map((option) => (
                        <Link
                            key={option.value}
                            href={payments.index({
                                query: { filter: option.value },
                            })}
                            className={cn(
                                'min-h-10 rounded-lg border px-3 py-2 text-sm font-medium transition-colors',
                                filter === option.value
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'bg-background hover:bg-accent',
                            )}
                        >
                            {option.label}
                        </Link>
                    ))}
                </div>

                {rows.length === 0 ? (
                    <EmptyState
                        icon={Receipt}
                        title="Nenhuma mensalidade aqui"
                        description="Não há mensalidades nesta situação."
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
                                        <p className="truncate font-semibold">
                                            {row.student}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            R$ {row.amount} ·{' '}
                                            {row.paid_at
                                                ? `pago em ${row.paid_at}`
                                                : `vence em ${row.due_date}`}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {row.is_automatic
                                                ? 'Gerado automaticamente'
                                                : row.recorded_by
                                                  ? `Lançado por ${row.recorded_by}`
                                                  : 'Lançamento manual'}
                                            {row.method && ` · ${row.method}`}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            row.is_overdue
                                                ? 'destructive'
                                                : 'secondary'
                                        }
                                        className="shrink-0"
                                    >
                                        {row.status}
                                    </Badge>
                                </div>

                                <div className="mt-3 flex flex-wrap gap-2">
                                    {row.has_receipt && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <a
                                                href={paymentReceipts.show.url(
                                                    row.id,
                                                )}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <ExternalLink className="size-4" />
                                                Comprovante
                                                {row.receipt_uploaded_at &&
                                                    ` · ${row.receipt_uploaded_at}`}
                                            </a>
                                        </Button>
                                    )}
                                    {!row.is_settled && (
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => setSettling(row)}
                                            data-test="mark-paid-button"
                                        >
                                            <CircleCheck className="size-4" />
                                            Marcar como pago
                                        </Button>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <Dialog
                open={settling !== null}
                onOpenChange={(open) => !open && setSettling(null)}
            >
                <DialogContent>
                    <DialogTitle>Registrar pagamento</DialogTitle>
                    <DialogDescription>
                        {settling &&
                            `${settling.student} · R$ ${settling.amount}. O aluno volta a poder fazer check-in.`}
                    </DialogDescription>

                    {settling && (
                        <Form
                            {...payments.pay.form(settling.id)}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setSettling(null)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="method">
                                            Forma de pagamento
                                        </Label>
                                        <Select
                                            name="method"
                                            defaultValue="pix"
                                        >
                                            <SelectTrigger id="method">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {methods.map((method) => (
                                                    <SelectItem
                                                        key={method.value}
                                                        value={method.value}
                                                    >
                                                        {method.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.method} />
                                    </div>

                                    <DialogFooter>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="confirm-paid-button"
                                        >
                                            {processing && <Spinner />}
                                            Confirmar pagamento
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

PaymentsIndex.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
