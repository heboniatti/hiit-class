import { Form } from '@inertiajs/react';
import { Ban, Check, CircleCheck } from 'lucide-react';
import CheckInDialog from '@/components/check-in-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { destroy } from '@/routes/check-ins';
import type { ClassCard } from '@/types';

/**
 * The main action of the student's experience.
 *
 * It has four states, and each one says plainly what is going on: bookable,
 * already booked (with the option to give the seat back), refused with the
 * reason spelled out, or the class is over.
 */
export default function CheckInButton({ classCard }: { classCard: ClassCard }) {
    if (classCard.is_checked_in) {
        return (
            <div className="space-y-3">
                <div className="flex items-center justify-center gap-2 rounded-lg border border-primary/30 bg-primary/10 p-4 text-primary">
                    <CircleCheck className="size-5 shrink-0" />
                    <span className="font-semibold">
                        Sua vaga está reservada
                    </span>
                </div>

                {classCard.can_cancel ? (
                    <Form
                        {...destroy.form({ classSession: classCard.id })}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                className="h-12 w-full text-base"
                                disabled={processing}
                                data-test="cancel-check-in-button"
                            >
                                {processing ? <Spinner /> : <Ban />}
                                Cancelar meu check-in
                            </Button>
                        )}
                    </Form>
                ) : (
                    <p className="text-center text-sm text-muted-foreground">
                        O prazo para cancelar já encerrou.
                    </p>
                )}
            </div>
        );
    }

    if (classCard.can_check_in) {
        return (
            <CheckInDialog
                classCard={classCard}
                trigger={
                    <Button
                        className="h-14 w-full text-lg"
                        data-test="check-in-button"
                    >
                        <Check className="size-5" />
                        Fazer check-in
                    </Button>
                }
            />
        );
    }

    return (
        <div className="space-y-2">
            <Button className="h-14 w-full text-lg" disabled>
                Check-in indisponível
            </Button>
            {classCard.denial_reason && (
                <p
                    className="text-center text-sm text-muted-foreground"
                    data-test="denial-reason"
                >
                    {classCard.denial_reason}
                </p>
            )}
        </div>
    );
}
