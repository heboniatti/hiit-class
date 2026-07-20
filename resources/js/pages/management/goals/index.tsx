import { Form, Head } from '@inertiajs/react';
import { Pencil, Plus, Target, Users } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import GoalProgress from '@/components/goal-progress';
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
import goals from '@/routes/management/goals';

type GoalRow = {
    id: number;
    title: string;
    description: string | null;
    type: string;
    type_label: string;
    unit: string;
    is_collective: boolean;
    student: string | null;
    user_id: number | null;
    target_value: number;
    starts_on: string;
    ends_on: string;
    ends_on_label: string;
    status: string;
    status_label: string;
    current: number;
    target: number;
    percent: number;
    is_complete: boolean;
};

type Option = { value: string; label: string };

type Props = {
    goals: GoalRow[];
    students: { value: number; label: string }[];
    types: Option[];
    statuses: Option[];
};

function GoalFields({
    goal,
    students,
    types,
    statuses,
    errors,
}: {
    goal?: GoalRow;
    students: { value: number; label: string }[];
    types: Option[];
    statuses: Option[];
    errors: Record<string, string>;
}) {
    const [isCollective, setIsCollective] = useState(
        goal?.is_collective ?? false,
    );

    return (
        <div className="space-y-4">
            <div className="grid gap-2">
                <Label htmlFor="title">Nome da meta</Label>
                <Input
                    id="title"
                    name="title"
                    defaultValue={goal?.title}
                    required
                    placeholder="Participar de 8 aulas no mês"
                />
                <InputError message={errors.title} />
            </div>

            <label className="flex items-start gap-3 rounded-lg border p-3">
                <input
                    type="checkbox"
                    name="is_collective"
                    value="1"
                    checked={isCollective}
                    onChange={(e) => setIsCollective(e.target.checked)}
                    className="mt-0.5 size-5 accent-primary"
                />
                <span>
                    <span className="font-medium">Meta do grupo</span>
                    <span className="block text-sm text-muted-foreground">
                        Vale para todos os alunos. Os alunos veem só o total do
                        grupo, nunca a contribuição de cada um.
                    </span>
                </span>
            </label>

            {!isCollective && (
                <div className="grid gap-2">
                    <Label htmlFor="user_id">Aluno</Label>
                    <Select
                        name="user_id"
                        defaultValue={
                            goal?.user_id ? String(goal.user_id) : undefined
                        }
                    >
                        <SelectTrigger id="user_id">
                            <SelectValue placeholder="Escolha o aluno" />
                        </SelectTrigger>
                        <SelectContent>
                            {students.map((student) => (
                                <SelectItem
                                    key={student.value}
                                    value={String(student.value)}
                                >
                                    {student.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.user_id} />
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="type">Tipo de meta</Label>
                <Select
                    name="type"
                    defaultValue={goal?.type ?? 'classes_in_period'}
                >
                    <SelectTrigger id="type">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {types.map((type) => (
                            <SelectItem key={type.value} value={type.value}>
                                {type.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="target_value">Quantidade necessária</Label>
                <Input
                    id="target_value"
                    name="target_value"
                    type="number"
                    inputMode="numeric"
                    min={1}
                    defaultValue={goal?.target_value ?? 8}
                    required
                />
                <InputError message={errors.target_value} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="starts_on">Início</Label>
                    <Input
                        id="starts_on"
                        name="starts_on"
                        type="date"
                        defaultValue={goal?.starts_on}
                        required
                    />
                    <InputError message={errors.starts_on} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="ends_on">Término</Label>
                    <Input
                        id="ends_on"
                        name="ends_on"
                        type="date"
                        defaultValue={goal?.ends_on}
                        required
                    />
                    <InputError message={errors.ends_on} />
                </div>
            </div>

            {goal && (
                <div className="grid gap-2">
                    <Label htmlFor="status">Situação</Label>
                    <Select name="status" defaultValue={goal.status}>
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
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="description">
                    Descrição{' '}
                    <span className="text-muted-foreground">(opcional)</span>
                </Label>
                <Input
                    id="description"
                    name="description"
                    defaultValue={goal?.description ?? ''}
                />
            </div>
        </div>
    );
}

export default function GoalsIndex({
    goals: rows,
    students,
    types,
    statuses,
}: Props) {
    const [editing, setEditing] = useState<GoalRow | null>(null);
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title="Metas" />

            <div className="mx-auto w-full max-w-3xl space-y-4 p-4">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold">Metas</h1>

                    <Dialog open={creating} onOpenChange={setCreating}>
                        <DialogTrigger asChild>
                            <Button data-test="new-goal-button">
                                <Plus className="size-4" />
                                Nova meta
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[90vh] overflow-y-auto">
                            <DialogTitle>Nova meta</DialogTitle>
                            <DialogDescription>
                                Metas incentivam a rotina. Não existe ranking
                                entre alunos.
                            </DialogDescription>

                            <Form
                                {...goals.store.form()}
                                options={{ preserveScroll: true }}
                                onSuccess={() => setCreating(false)}
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <GoalFields
                                            students={students}
                                            types={types}
                                            statuses={statuses}
                                            errors={errors}
                                        />
                                        <DialogFooter className="mt-4">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                data-test="save-goal-button"
                                            >
                                                {processing && <Spinner />}
                                                Criar meta
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
                        icon={Target}
                        title="Nenhuma meta criada"
                        description="Crie metas individuais ou uma meta para o grupo inteiro."
                    />
                ) : (
                    <ul className="space-y-3">
                        {rows.map((row) => (
                            <li
                                key={row.id}
                                className="space-y-3 rounded-xl border bg-card p-4"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="flex items-center gap-2 font-semibold">
                                            {row.is_collective && (
                                                <Users className="size-4 shrink-0 text-primary" />
                                            )}
                                            <span className="truncate">
                                                {row.title}
                                            </span>
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {row.is_collective
                                                ? 'Grupo todo'
                                                : row.student}{' '}
                                            · {row.type_label} · até{' '}
                                            {row.ends_on_label}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 flex-col items-end gap-2">
                                        <Badge variant="secondary">
                                            {row.status_label}
                                        </Badge>
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

                                <GoalProgress
                                    current={row.current}
                                    target={row.target}
                                    percent={row.percent}
                                    unit={row.unit}
                                    isComplete={row.is_complete}
                                />
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
                    <DialogTitle>Editar meta</DialogTitle>
                    <DialogDescription>
                        O progresso é recalculado a partir das presenças.
                    </DialogDescription>

                    {editing && (
                        <Form
                            {...goals.update.form(editing.id)}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setEditing(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <GoalFields
                                        goal={editing}
                                        students={students}
                                        types={types}
                                        statuses={statuses}
                                        errors={errors}
                                    />
                                    <DialogFooter className="mt-4">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="update-goal-button"
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

GoalsIndex.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
