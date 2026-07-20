import { Form, Head } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PhoneInput from '@/components/phone-input';
import PinInput from '@/components/pin-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import students from '@/routes/management/students';

type Props = {
    student: {
        id: number;
        name: string;
        phone: string;
        email: string | null;
        status: string;
        is_active: boolean;
        notes: string | null;
        emergency_contact_name: string | null;
        emergency_contact_phone: string | null;
        registered_at: string;
        plan_id: number | null;
        plan_name: string | null;
        plan_started_at: string | null;
        available_check_ins: number | null;
        has_plan: boolean;
        attended_last_month: number;
    };
    history: { id: number; name: string; date: string; status: string }[];
    plans: { value: number; label: string; disabled: boolean }[];
};

export default function StudentShow({ student, history, plans }: Props) {
    return (
        <>
            <Head title={student.name} />

            <div className="mx-auto w-full max-w-3xl space-y-8 p-4">
                <header className="space-y-2">
                    <h1 className="text-2xl font-bold">{student.name}</h1>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge
                            variant={
                                student.is_active ? 'default' : 'secondary'
                            }
                        >
                            {student.is_active ? 'Ativo' : 'Inativo'}
                        </Badge>
                        <Badge variant="secondary">
                            {student.plan_name ?? 'Sem plano'}
                        </Badge>
                        <span className="text-sm text-muted-foreground">
                            Cadastrado em {student.registered_at}
                        </span>
                    </div>
                    {!student.has_plan && (
                        <p className="rounded-lg bg-primary/10 p-3 text-sm text-primary">
                            Este aluno ainda não tem plano recorrente. Enquanto
                            houver um experimental disponível, ele poderá fazer
                            um check-in; escolha um plano abaixo para continuar.
                        </p>
                    )}
                </header>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border bg-card p-4">
                        <p className="text-sm text-muted-foreground">
                            Check-ins disponíveis
                        </p>
                        <p className="mt-1 text-2xl font-bold">
                            {student.has_plan
                                ? (student.available_check_ins ?? 'Ilimitado')
                                : '—'}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-4">
                        <p className="text-sm text-muted-foreground">
                            Aulas nas últimas 4 semanas
                        </p>
                        <p className="mt-1 text-2xl font-bold">
                            {student.attended_last_month}
                        </p>
                    </div>
                </div>

                <Form
                    {...students.update.form(student.id)}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Heading
                                variant="small"
                                title="Dados do aluno"
                                description="Somente professores e administradores veem esta tela"
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="name">Nome completo</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={student.name}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telefone</Label>
                                <PhoneInput
                                    id="phone"
                                    name="phone"
                                    defaultValue={student.phone}
                                    required
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="plan_id">Plano</Label>
                                <Select
                                    name="plan_id"
                                    defaultValue={
                                        student.plan_id
                                            ? String(student.plan_id)
                                            : undefined
                                    }
                                >
                                    <SelectTrigger id="plan_id">
                                        <SelectValue placeholder="Escolha um plano" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {plans.map((plan) => (
                                            <SelectItem
                                                key={plan.value}
                                                value={String(plan.value)}
                                                disabled={plan.disabled}
                                            >
                                                {plan.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {student.plan_started_at && (
                                    <p className="text-sm text-muted-foreground">
                                        No plano atual desde{' '}
                                        {student.plan_started_at}.
                                    </p>
                                )}
                                <InputError message={errors.plan_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Situação</Label>
                                <Select
                                    name="status"
                                    defaultValue={student.status}
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">
                                            Ativo
                                        </SelectItem>
                                        <SelectItem value="inactive">
                                            Inativo
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-sm text-muted-foreground">
                                    Um aluno inativo não entra no sistema, mas o
                                    histórico dele é preservado.
                                </p>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">
                                    Observações internas
                                </Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    defaultValue={student.notes ?? ''}
                                    className="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring focus-visible:outline-none"
                                    placeholder="Lesões, restrições, combinados..."
                                />
                                <p className="text-sm text-muted-foreground">
                                    O aluno nunca vê estas observações.
                                </p>
                                <InputError message={errors.notes} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="emergency_contact_name">
                                        Contato de emergência
                                    </Label>
                                    <Input
                                        id="emergency_contact_name"
                                        name="emergency_contact_name"
                                        defaultValue={
                                            student.emergency_contact_name ?? ''
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="emergency_contact_phone">
                                        Telefone de emergência
                                    </Label>
                                    <PhoneInput
                                        id="emergency_contact_phone"
                                        name="emergency_contact_phone"
                                        defaultValue={
                                            student.emergency_contact_phone ??
                                            ''
                                        }
                                    />
                                </div>
                            </div>

                            <Button
                                disabled={processing}
                                data-test="save-student-button"
                            >
                                {processing && <Spinner />}
                                Salvar
                            </Button>
                        </>
                    )}
                </Form>

                <Form
                    {...students.pin.update.form(student.id)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="space-y-4 rounded-xl border p-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <Heading
                                variant="small"
                                title="Redefinir PIN"
                                description="Use quando o aluno esquecer o PIN. Informe o novo PIN a ele pessoalmente."
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="reset_password">Novo PIN</Label>
                                <PinInput id="reset_password" name="password" />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reset_password_confirmation">
                                    Repita o novo PIN
                                </Label>
                                <PinInput
                                    id="reset_password_confirmation"
                                    name="password_confirmation"
                                />
                            </div>

                            <Button
                                variant="secondary"
                                disabled={processing}
                                data-test="reset-pin-button"
                            >
                                {processing ? <Spinner /> : <KeyRound />}
                                Redefinir PIN
                            </Button>
                        </>
                    )}
                </Form>

                {history.length > 0 && (
                    <section className="space-y-3">
                        <Heading
                            variant="small"
                            title="Histórico recente"
                            description="Últimas aulas do aluno"
                        />
                        <ul className="space-y-2">
                            {history.map((row) => (
                                <li
                                    key={row.id}
                                    className="flex items-center justify-between gap-3 rounded-lg border bg-card p-3 text-sm"
                                >
                                    <span className="truncate">
                                        {row.name} · {row.date}
                                    </span>
                                    <span className="shrink-0 text-muted-foreground">
                                        {row.status}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}

StudentShow.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
