import { Form, Head } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PhoneInput from '@/components/phone-input';
import PinInput from '@/components/pin-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatPhone } from '@/lib/phone';
import { dashboard } from '@/routes';
import teachers from '@/routes/management/teachers';

type TeacherRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    status: string;
    is_active: boolean;
    registered_at: string;
};

type Props = {
    teachers: TeacherRow[];
};

export default function TeachersIndex({ teachers: rows }: Props) {
    return (
        <>
            <Head title="Professores" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <Heading
                    title="Professores"
                    description="Cadastre quem terá acesso à agenda, alunos e pagamentos"
                />

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
                    <section className="rounded-xl border bg-card p-4 sm:p-6">
                        <h2 className="text-lg font-semibold">
                            Cadastrar professor
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            O professor entrará usando o telefone e o PIN
                            inicial.
                        </p>

                        <Form
                            {...teachers.store.form()}
                            className="mt-6 space-y-5"
                            resetOnSuccess
                            resetOnError={['password', 'password_confirmation']}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Nome completo
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            autoFocus
                                            autoComplete="name"
                                            placeholder="Nome do professor"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Telefone</Label>
                                        <PhoneInput
                                            id="phone"
                                            name="phone"
                                            required
                                        />
                                        <InputError message={errors.phone} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">
                                            E-mail{' '}
                                            <span className="text-muted-foreground">
                                                (opcional)
                                            </span>
                                        </Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            autoComplete="email"
                                            placeholder="professor@exemplo.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            PIN inicial
                                        </Label>
                                        <PinInput
                                            id="password"
                                            name="password"
                                            required
                                        />
                                        <p className="text-sm text-muted-foreground">
                                            Informe este PIN ao professor. Ele
                                            poderá trocá-lo depois.
                                        </p>
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">
                                            Repita o PIN
                                        </Label>
                                        <PinInput
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            required
                                        />
                                    </div>

                                    <Button
                                        disabled={processing}
                                        data-test="create-teacher-button"
                                    >
                                        {processing && <Spinner />}
                                        Cadastrar professor
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>

                    <section className="space-y-3">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Equipe cadastrada
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {rows.length} professor(es)
                            </p>
                        </div>

                        {rows.length === 0 ? (
                            <EmptyState
                                icon={GraduationCap}
                                title="Nenhum professor cadastrado"
                                description="O primeiro professor aparecerá aqui depois do cadastro."
                            />
                        ) : (
                            <ul className="space-y-3">
                                {rows.map((teacher) => (
                                    <li
                                        key={teacher.id}
                                        className="flex items-start justify-between gap-3 rounded-xl border bg-card p-4"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-semibold">
                                                {teacher.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {formatPhone(teacher.phone)}
                                            </p>
                                            {teacher.email && (
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {teacher.email}
                                                </p>
                                            )}
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Cadastrado em{' '}
                                                {teacher.registered_at}
                                            </p>
                                        </div>
                                        <Badge
                                            variant={
                                                teacher.is_active
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                        >
                                            {teacher.status}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

TeachersIndex.layout = {
    breadcrumbs: [
        { title: 'Painel', href: dashboard() },
        { title: 'Professores', href: teachers.index() },
    ],
};
