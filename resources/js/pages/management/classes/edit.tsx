import { Form, Head } from '@inertiajs/react';
import { Ban, Repeat } from 'lucide-react';
import ClassFormFields from '@/components/class-form-fields';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import classes from '@/routes/management/classes';

type Props = {
    classSession: {
        id: number;
        name: string;
        date: string;
        start_time: string;
        end_time: string;
        max_students: number;
        teacher_id: number;
        status: string;
        notes: string | null;
        taken: number;
        is_recurring: boolean;
    };
    teachers: { value: number; label: string }[];
    statuses: { value: string; label: string }[];
};

export default function ClassEdit({ classSession, teachers, statuses }: Props) {
    return (
        <>
            <Head title={`Editar — ${classSession.name}`} />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <Heading
                    title="Editar aula"
                    description={`${classSession.taken} aluno(s) com vaga reservada`}
                />

                {classSession.is_recurring && (
                    <p className="flex items-start gap-2 rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                        <Repeat className="mt-0.5 size-4 shrink-0" />
                        Esta aula faz parte de uma série que se repete. As
                        mudanças valem só para este dia.
                    </p>
                )}

                <Form
                    {...classes.update.form({ classSession: classSession.id })}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="is_recurring"
                                value="0"
                            />

                            <ClassFormFields
                                errors={errors}
                                values={classSession}
                                teachers={teachers}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="status">Situação</Label>
                                <Select
                                    name="status"
                                    defaultValue={classSession.status}
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
                                <InputError message={errors.status} />
                            </div>

                            <Button
                                disabled={processing}
                                className="h-12 text-base"
                                data-test="save-class-button"
                            >
                                {processing && <Spinner />}
                                Salvar alterações
                            </Button>
                        </>
                    )}
                </Form>

                <div className="space-y-3 rounded-xl border border-destructive/30 p-4">
                    <Heading
                        variant="small"
                        title="Cancelar esta aula"
                        description="Os alunos recebem o check-in de volta. O histórico da aula é preservado."
                    />

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="destructive"
                                data-test="cancel-class-button"
                            >
                                <Ban />
                                Cancelar aula
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Cancelar esta aula?</DialogTitle>
                            <DialogDescription>
                                {classSession.taken > 0
                                    ? `${classSession.taken} aluno(s) perdem a vaga e recebem o check-in de volta.`
                                    : 'Nenhum aluno tem vaga reservada nesta aula.'}
                            </DialogDescription>

                            <Form
                                {...classes.destroy.form({
                                    classSession: classSession.id,
                                })}
                            >
                                {({ processing }) => (
                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Voltar
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                            asChild
                                        >
                                            <button
                                                type="submit"
                                                data-test="confirm-cancel-class-button"
                                            >
                                                Sim, cancelar aula
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </>
    );
}

ClassEdit.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
