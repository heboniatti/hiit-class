import { Form, Head } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import attendance from '@/routes/management/classes/attendance';

type Student = {
    check_in_id: number;
    name: string;
    phone: string;
    status: string;
    checked_in_at: string;
};

type Props = {
    classSession: {
        id: number;
        name: string;
        date: string;
        starts_at: string;
        ends_at: string;
        teacher: string;
        max_students: number;
        taken: number;
    };
    students: Student[];
    options: { value: string; label: string }[];
};

export default function Attendance({ classSession, students, options }: Props) {
    // Uncontrolled inputs are the project's convention, but a segmented picker
    // has no native form control, so the marks are held here and submitted as
    // hidden fields.
    const [marks, setMarks] = useState<Record<number, string>>(
        Object.fromEntries(students.map((s) => [s.check_in_id, s.status])),
    );

    return (
        <>
            <Head title={`Presença — ${classSession.name}`} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <header>
                    <h1 className="text-2xl font-bold">Lista de presença</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {classSession.name} · {classSession.date} às{' '}
                        {classSession.starts_at} · {classSession.taken} de{' '}
                        {classSession.max_students} vagas
                    </p>
                </header>

                {students.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title="Nenhum aluno inscrito"
                        description="Quando algum aluno fizer check-in nesta aula, ele aparece aqui."
                    />
                ) : (
                    <Form
                        {...attendance.update.form({
                            classSession: classSession.id,
                        })}
                        options={{ preserveScroll: true }}
                        className="space-y-4"
                    >
                        {({ processing }) => (
                            <>
                                <ul className="space-y-3">
                                    {students.map((student) => (
                                        <li
                                            key={student.check_in_id}
                                            className="rounded-xl border bg-card p-4"
                                        >
                                            <div className="mb-3">
                                                <p className="font-semibold">
                                                    {student.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    Check-in em{' '}
                                                    {student.checked_in_at}
                                                </p>
                                            </div>

                                            <input
                                                type="hidden"
                                                name={`statuses[${student.check_in_id}]`}
                                                value={
                                                    marks[student.check_in_id]
                                                }
                                            />

                                            <div
                                                role="group"
                                                aria-label={`Presença de ${student.name}`}
                                                className="flex flex-wrap gap-2"
                                            >
                                                {options.map((option) => {
                                                    const active =
                                                        marks[
                                                            student.check_in_id
                                                        ] === option.value;

                                                    return (
                                                        <button
                                                            key={option.value}
                                                            type="button"
                                                            aria-pressed={
                                                                active
                                                            }
                                                            onClick={() =>
                                                                setMarks(
                                                                    (prev) => ({
                                                                        ...prev,
                                                                        [student.check_in_id]:
                                                                            option.value,
                                                                    }),
                                                                )
                                                            }
                                                            className={cn(
                                                                'min-h-11 rounded-lg border px-3 text-sm font-medium transition-colors',
                                                                active
                                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                                    : 'bg-background hover:bg-accent',
                                                            )}
                                                        >
                                                            {option.label}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </li>
                                    ))}
                                </ul>

                                <Button
                                    type="submit"
                                    className="h-12 w-full text-base"
                                    disabled={processing}
                                    data-test="save-attendance-button"
                                >
                                    {processing && <Spinner />}
                                    Salvar presenças
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

Attendance.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
