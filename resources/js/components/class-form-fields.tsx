import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const WEEKDAYS = [
    { iso: 1, short: 'Seg' },
    { iso: 2, short: 'Ter' },
    { iso: 3, short: 'Qua' },
    { iso: 4, short: 'Qui' },
    { iso: 5, short: 'Sex' },
    { iso: 6, short: 'Sáb' },
    { iso: 7, short: 'Dom' },
];

export type ClassFormValues = {
    name?: string;
    date?: string;
    start_time?: string;
    end_time?: string;
    max_students?: number;
    teacher_id?: number;
    notes?: string | null;
};

/**
 * The fields shared by creating and editing a class.
 *
 * Recurrence is offered only when creating: editing one occurrence must not
 * silently rewrite every other one.
 */
export default function ClassFormFields({
    errors,
    values,
    teachers,
    allowRecurrence = false,
    today,
}: {
    errors: Record<string, string>;
    values?: ClassFormValues;
    teachers: { value: number; label: string }[];
    allowRecurrence?: boolean;
    today?: string;
}) {
    const [isRecurring, setIsRecurring] = useState(false);
    const [days, setDays] = useState<number[]>([]);

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">Nome da aula</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={values?.name}
                    required
                    autoFocus
                    placeholder="HIIT Noite"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="teacher_id">Professor responsável</Label>
                <Select
                    name="teacher_id"
                    defaultValue={
                        values?.teacher_id
                            ? String(values.teacher_id)
                            : undefined
                    }
                >
                    <SelectTrigger id="teacher_id">
                        <SelectValue placeholder="Escolha o professor" />
                    </SelectTrigger>
                    <SelectContent>
                        {teachers.map((teacher) => (
                            <SelectItem
                                key={teacher.value}
                                value={String(teacher.value)}
                            >
                                {teacher.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.teacher_id} />
            </div>

            {allowRecurrence && (
                <div className="space-y-3 rounded-xl border p-4">
                    <label className="flex items-center gap-3">
                        <input
                            type="checkbox"
                            name="is_recurring"
                            value="1"
                            checked={isRecurring}
                            onChange={(e) => setIsRecurring(e.target.checked)}
                            className="size-5 accent-primary"
                        />
                        <span className="font-medium">
                            Esta aula se repete toda semana
                        </span>
                    </label>

                    {isRecurring && (
                        <div className="space-y-4 pt-2">
                            <div className="grid gap-2">
                                <Label>Dias da semana</Label>
                                <div className="flex flex-wrap gap-2">
                                    {WEEKDAYS.map((day) => {
                                        const active = days.includes(day.iso);

                                        return (
                                            <button
                                                key={day.iso}
                                                type="button"
                                                aria-pressed={active}
                                                onClick={() =>
                                                    setDays((prev) =>
                                                        prev.includes(day.iso)
                                                            ? prev.filter(
                                                                  (d) =>
                                                                      d !==
                                                                      day.iso,
                                                              )
                                                            : [
                                                                  ...prev,
                                                                  day.iso,
                                                              ],
                                                    )
                                                }
                                                className={cn(
                                                    'min-h-11 min-w-14 rounded-lg border text-sm font-medium transition-colors',
                                                    active
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'bg-background hover:bg-accent',
                                                )}
                                            >
                                                {day.short}
                                            </button>
                                        );
                                    })}
                                </div>
                                {days.map((day) => (
                                    <input
                                        key={day}
                                        type="hidden"
                                        name="days_of_week[]"
                                        value={day}
                                    />
                                ))}
                                <InputError message={errors.days_of_week} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="starts_on">
                                        A partir de
                                    </Label>
                                    <Input
                                        id="starts_on"
                                        name="starts_on"
                                        type="date"
                                        defaultValue={today}
                                    />
                                    <InputError message={errors.starts_on} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="ends_on">
                                        Até{' '}
                                        <span className="text-muted-foreground">
                                            (opcional)
                                        </span>
                                    </Label>
                                    <Input
                                        id="ends_on"
                                        name="ends_on"
                                        type="date"
                                    />
                                    <InputError message={errors.ends_on} />
                                </div>
                            </div>

                            <p className="text-sm text-muted-foreground">
                                As aulas são criadas para as próximas semanas e
                                continuam sendo geradas sozinhas.
                            </p>
                        </div>
                    )}
                </div>
            )}

            {!isRecurring && (
                <div className="grid gap-2">
                    <Label htmlFor="date">Data</Label>
                    <Input
                        id="date"
                        name="date"
                        type="date"
                        defaultValue={values?.date ?? today}
                        required
                    />
                    <InputError message={errors.date} />
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="start_time">Início</Label>
                    <Input
                        id="start_time"
                        name="start_time"
                        type="time"
                        defaultValue={values?.start_time ?? '19:00'}
                        required
                    />
                    <InputError message={errors.start_time} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="end_time">Término</Label>
                    <Input
                        id="end_time"
                        name="end_time"
                        type="time"
                        defaultValue={values?.end_time ?? '20:00'}
                        required
                    />
                    <InputError message={errors.end_time} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="max_students">Máximo de alunos</Label>
                <Input
                    id="max_students"
                    name="max_students"
                    type="number"
                    inputMode="numeric"
                    min={1}
                    defaultValue={values?.max_students ?? 20}
                    required
                />
                <InputError message={errors.max_students} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="notes">
                    Observações{' '}
                    <span className="text-muted-foreground">(opcional)</span>
                </Label>
                <textarea
                    id="notes"
                    name="notes"
                    rows={2}
                    defaultValue={values?.notes ?? ''}
                    className="min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring focus-visible:outline-none"
                    placeholder="Traga corda e toalha"
                />
                <InputError message={errors.notes} />
            </div>
        </>
    );
}
