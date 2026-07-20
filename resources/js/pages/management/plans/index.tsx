import { Form, Head } from '@inertiajs/react';
import { Pencil, Plus, Ticket } from 'lucide-react';
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
import { dashboard } from '@/routes';
import plans from '@/routes/management/plans';

type PlanRow = {
    id: number;
    name: string;
    description: string | null;
    type: string;
    type_label: string;
    checkins_per_week: number | null;
    max_students: number | null;
    allowance: string;
    price: string;
    price_raw: number;
    payment_due_day: number;
    allows_rollover: boolean;
    is_active: boolean;
    active_students: number;
    is_full: boolean;
};

type Props = {
    plans: PlanRow[];
    types: { value: string; label: string }[];
};

function PlanFields({
    plan,
    types,
    errors,
}: {
    plan?: PlanRow;
    types: { value: string; label: string }[];
    errors: Record<string, string>;
}) {
    const [type, setType] = useState(plan?.type ?? 'weekly');

    return (
        <div className="space-y-4">
            <div className="grid gap-2">
                <Label htmlFor="name">Nome do plano</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={plan?.name}
                    required
                    placeholder="Plano 3x por semana"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="type">Tipo</Label>
                <Select name="type" value={type} onValueChange={setType}>
                    <SelectTrigger id="type">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {types.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>

            {type === 'weekly' ? (
                <div className="grid gap-2">
                    <Label htmlFor="checkins_per_week">
                        Check-ins por semana
                    </Label>
                    <Input
                        id="checkins_per_week"
                        name="checkins_per_week"
                        type="number"
                        inputMode="numeric"
                        min={1}
                        defaultValue={plan?.checkins_per_week ?? ''}
                        placeholder="Deixe vazio para ilimitado"
                    />
                    <p className="text-sm text-muted-foreground">
                        Vazio significa check-ins ilimitados.
                    </p>
                    <InputError message={errors.checkins_per_week} />
                </div>
            ) : (
                <p className="rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                    O plano experimental dá exatamente 1 check-in. Depois de
                    usar, o aluno precisa de outro plano para treinar.
                </p>
            )}

            <div className="grid gap-2">
                <Label htmlFor="max_students">Limite de alunos</Label>
                <Input
                    id="max_students"
                    name="max_students"
                    type="number"
                    inputMode="numeric"
                    min={1}
                    defaultValue={plan?.max_students ?? ''}
                    placeholder="Deixe vazio para ilimitado"
                />
                <p className="text-sm text-muted-foreground">
                    Vazio significa que o plano aceita alunos ilimitados.
                </p>
                <InputError message={errors.max_students} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="price">Mensalidade (R$)</Label>
                    <Input
                        id="price"
                        name="price"
                        type="number"
                        inputMode="decimal"
                        step="0.01"
                        min={0}
                        defaultValue={plan?.price_raw ?? 0}
                        required
                    />
                    <InputError message={errors.price} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="payment_due_day">Dia do vencimento</Label>
                    <Input
                        id="payment_due_day"
                        name="payment_due_day"
                        type="number"
                        inputMode="numeric"
                        min={1}
                        max={28}
                        defaultValue={plan?.payment_due_day ?? 10}
                        required
                    />
                    <InputError message={errors.payment_due_day} />
                </div>
            </div>

            {type === 'weekly' && (
                <label className="flex items-start gap-3 rounded-lg border p-3">
                    <input
                        type="checkbox"
                        name="allows_rollover"
                        value="1"
                        defaultChecked={plan?.allows_rollover}
                        className="mt-0.5 size-5 accent-primary"
                    />
                    <span>
                        <span className="font-medium">
                            Acumular check-ins não usados
                        </span>
                        <span className="block text-sm text-muted-foreground">
                            Sobra da semana anterior, no máximo uma semana.
                        </span>
                    </span>
                </label>
            )}

            <label className="flex items-start gap-3 rounded-lg border p-3">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    defaultChecked={plan?.is_active ?? true}
                    className="mt-0.5 size-5 accent-primary"
                />
                <span>
                    <span className="font-medium">Plano ativo</span>
                    <span className="block text-sm text-muted-foreground">
                        Planos inativos não aparecem para vincular a alunos.
                    </span>
                </span>
            </label>

            <div className="grid gap-2">
                <Label htmlFor="description">
                    Descrição{' '}
                    <span className="text-muted-foreground">(opcional)</span>
                </Label>
                <Input
                    id="description"
                    name="description"
                    defaultValue={plan?.description ?? ''}
                    placeholder="Três treinos por semana"
                />
                <InputError message={errors.description} />
            </div>
        </div>
    );
}

export default function PlansIndex({ plans: rows, types }: Props) {
    const [editing, setEditing] = useState<PlanRow | null>(null);
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title="Planos" />

            <div className="mx-auto w-full max-w-3xl space-y-4 p-4">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold">Planos</h1>

                    <Dialog open={creating} onOpenChange={setCreating}>
                        <DialogTrigger asChild>
                            <Button data-test="new-plan-button">
                                <Plus className="size-4" />
                                Novo plano
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[90vh] overflow-y-auto">
                            <DialogTitle>Novo plano</DialogTitle>
                            <DialogDescription>
                                Defina quantos treinos por semana e o valor da
                                mensalidade.
                            </DialogDescription>

                            <Form
                                {...plans.store.form()}
                                options={{ preserveScroll: true }}
                                onSuccess={() => setCreating(false)}
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <PlanFields
                                            types={types}
                                            errors={errors}
                                        />
                                        <DialogFooter className="mt-4">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                data-test="save-plan-button"
                                            >
                                                {processing && <Spinner />}
                                                Criar plano
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </header>

                {rows.length === 0 ? (
                    <EmptyState
                        icon={Ticket}
                        title="Nenhum plano cadastrado"
                        description="Crie o primeiro plano para poder vincular alunos."
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
                                            {row.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {row.allowance} · R$ {row.price} ·
                                            vence dia {row.payment_due_day}
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {row.max_students === null
                                                ? `${row.active_students} aluno(s) · capacidade ilimitada`
                                                : `${row.active_students} de ${row.max_students} aluno(s)`}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 flex-col items-end gap-2">
                                        <div className="flex gap-2">
                                            <Badge variant="secondary">
                                                {row.type_label}
                                            </Badge>
                                            {!row.is_active && (
                                                <Badge variant="secondary">
                                                    Inativo
                                                </Badge>
                                            )}
                                            {row.is_full && (
                                                <Badge variant="destructive">
                                                    Lotado
                                                </Badge>
                                            )}
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setEditing(row)}
                                        >
                                            <Pencil className="size-4" />
                                            Editar
                                        </Button>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto">
                    <DialogTitle>Editar plano</DialogTitle>
                    <DialogDescription>
                        As mudanças valem para todos os alunos neste plano.
                    </DialogDescription>

                    {editing && (
                        <Form
                            {...plans.update.form(editing.id)}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setEditing(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <PlanFields
                                        plan={editing}
                                        types={types}
                                        errors={errors}
                                    />
                                    <DialogFooter className="mt-4">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="update-plan-button"
                                        >
                                            {processing && <Spinner />}
                                            Salvar
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

PlansIndex.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
