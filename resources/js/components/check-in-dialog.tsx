import { Form } from '@inertiajs/react';
import { CalendarCheck, Clock, User } from 'lucide-react';
import { useState } from 'react';
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
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/check-ins';
import type { ClassCard } from '@/types';

/**
 * Confirms a check-in.
 *
 * A dialog rather than its own page: it keeps the student in the context of the
 * class they are looking at, and confirming costs one tap instead of a whole
 * navigation.
 */
export default function CheckInDialog({
    classCard,
    trigger,
}: {
    classCard: ClassCard;
    trigger: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>Confirmar seu check-in?</DialogTitle>
                <DialogDescription>
                    Sua vaga fica reservada para esta aula.
                </DialogDescription>

                <div className="space-y-3 rounded-lg border bg-muted/40 p-4">
                    <p className="text-lg font-semibold">{classCard.name}</p>
                    <p className="flex items-center gap-2 text-sm">
                        <CalendarCheck className="size-4 text-muted-foreground" />
                        {classCard.weekday}, {classCard.date}
                    </p>
                    <p className="flex items-center gap-2 text-sm">
                        <Clock className="size-4 text-muted-foreground" />
                        {classCard.starts_at} às {classCard.ends_at}
                    </p>
                    <p className="flex items-center gap-2 text-sm">
                        <User className="size-4 text-muted-foreground" />
                        {classCard.teacher}
                    </p>
                </div>

                <Form
                    {...store.form({ classSession: classCard.id })}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary" className="h-12">
                                    Voltar
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                className="h-12 text-base"
                                disabled={processing}
                                data-test="confirm-check-in-button"
                            >
                                {processing && <Spinner />}
                                Confirmar check-in
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
