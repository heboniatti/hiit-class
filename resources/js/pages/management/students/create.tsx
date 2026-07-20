import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PhoneInput from '@/components/phone-input';
import PinInput from '@/components/pin-input';
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
    plans: { value: number; label: string; disabled: boolean }[];
};

export default function StudentCreate({ plans }: Props) {
    return (
        <>
            <Head title="Cadastrar aluno" />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <Heading
                    title="Cadastrar aluno"
                    description="Para quem não consegue se cadastrar sozinho pelo celular"
                />

                <Form
                    {...students.store.form()}
                    className="space-y-6"
                    resetOnError={['password', 'password_confirmation']}
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="status" value="active" />

                            <div className="grid gap-2">
                                <Label htmlFor="name">Nome completo</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    placeholder="Nome do aluno"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telefone</Label>
                                <PhoneInput id="phone" name="phone" required />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="plan_id">
                                    Plano{' '}
                                    <span className="text-muted-foreground">
                                        (opcional)
                                    </span>
                                </Label>
                                <Select name="plan_id">
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
                                <p className="text-sm text-muted-foreground">
                                    Sem plano recorrente, o aluno poderá usar o
                                    experimental disponível.
                                </p>
                                <InputError message={errors.plan_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">PIN inicial</Label>
                                <PinInput
                                    id="password"
                                    name="password"
                                    required
                                />
                                <p className="text-sm text-muted-foreground">
                                    Informe este PIN ao aluno. Ele pode trocar
                                    depois.
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
                                data-test="create-student-button"
                            >
                                {processing && <Spinner />}
                                Cadastrar aluno
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

StudentCreate.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
