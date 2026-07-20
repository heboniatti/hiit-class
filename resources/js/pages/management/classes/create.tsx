import { Form, Head } from '@inertiajs/react';
import ClassFormFields from '@/components/class-form-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import classes from '@/routes/management/classes';

type Props = {
    teachers: { value: number; label: string }[];
    today: string;
};

export default function ClassCreate({ teachers, today }: Props) {
    return (
        <>
            <Head title="Nova aula" />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <Heading
                    title="Nova aula"
                    description="Crie uma aula avulsa ou uma que se repete toda semana"
                />

                <Form {...classes.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <ClassFormFields
                                errors={errors}
                                teachers={teachers}
                                today={today}
                                allowRecurrence
                            />

                            <Button
                                disabled={processing}
                                className="h-12 text-base"
                                data-test="create-class-button"
                            >
                                {processing && <Spinner />}
                                Criar aula
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ClassCreate.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
