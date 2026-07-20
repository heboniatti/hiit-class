import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import settings from '@/routes/management/settings';

type Settings = {
    checkin_opens_hours_before: number;
    checkin_closes_minutes_before: number;
    cancellation_deadline_minutes: number;
    session_horizon_weeks: number;
    low_frequency_threshold: number;
};

type Props = {
    settings: Settings;
};

const FIELDS: {
    name: keyof Settings;
    label: string;
    hint: string;
    unit: string;
}[] = [
    {
        name: 'checkin_opens_hours_before',
        label: 'Check-in abre quantas horas antes da aula',
        hint: 'Antes disso, o aluno vê a aula mas não consegue reservar.',
        unit: 'horas',
    },
    {
        name: 'checkin_closes_minutes_before',
        label: 'Check-in fecha quantos minutos antes da aula',
        hint: 'Zero significa que dá para fazer check-in até a aula começar.',
        unit: 'minutos',
    },
    {
        name: 'cancellation_deadline_minutes',
        label: 'Prazo para o aluno cancelar, antes da aula',
        hint: 'Depois disso a vaga não é mais liberada e o check-in não volta.',
        unit: 'minutos',
    },
    {
        name: 'session_horizon_weeks',
        label: 'Semanas de aulas geradas à frente',
        hint: 'Quanto tempo à frente as aulas que se repetem são criadas.',
        unit: 'semanas',
    },
    {
        name: 'low_frequency_threshold',
        label: 'Baixa frequência: menos de quantas aulas em 4 semanas',
        hint: 'Alunos abaixo disso aparecem no seu painel para você dar um alô.',
        unit: 'aulas',
    },
];

export default function GeneralSettings({ settings: values }: Props) {
    return (
        <>
            <Head title="Configurações" />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <Heading
                    title="Configurações gerais"
                    description="As regras que valem para o check-in de todos os alunos"
                />

                <Form
                    {...settings.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            {FIELDS.map((field) => (
                                <div key={field.name} className="grid gap-2">
                                    <Label htmlFor={field.name}>
                                        {field.label}
                                    </Label>
                                    <div className="flex items-center gap-3">
                                        <Input
                                            id={field.name}
                                            name={field.name}
                                            type="number"
                                            inputMode="numeric"
                                            min={0}
                                            defaultValue={values[field.name]}
                                            required
                                            className="max-w-32"
                                        />
                                        <span className="text-sm text-muted-foreground">
                                            {field.unit}
                                        </span>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {field.hint}
                                    </p>
                                    <InputError message={errors[field.name]} />
                                </div>
                            ))}

                            <Button
                                disabled={processing}
                                className="h-12 text-base"
                                data-test="save-settings-button"
                            >
                                {processing && <Spinner />}
                                Salvar configurações
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

GeneralSettings.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
