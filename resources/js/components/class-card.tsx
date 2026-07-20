import { Link } from '@inertiajs/react';
import { CircleCheck, Clock, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/classes';
import type { ClassCard as ClassCardType } from '@/types';

/**
 * One class in the list. Built for a thumb on a phone: the whole card is the
 * tap target, and the state is readable at a glance.
 */
export default function ClassCard({ classCard }: { classCard: ClassCardType }) {
    return (
        <Link
            href={show({ classSession: classCard.id })}
            className="block rounded-xl border bg-card p-4 transition-colors hover:border-primary/50 focus-visible:ring-[3px] focus-visible:ring-ring focus-visible:outline-none"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate text-base font-semibold">
                        {classCard.name}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {classCard.weekday}, {classCard.date}
                    </p>
                </div>

                {classCard.is_checked_in && (
                    <Badge className="shrink-0 gap-1">
                        <CircleCheck className="size-3" />
                        Reservada
                    </Badge>
                )}

                {!classCard.is_checked_in && classCard.is_full && (
                    <Badge variant="secondary" className="shrink-0">
                        Aula lotada
                    </Badge>
                )}
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                <span className="flex items-center gap-1.5">
                    <Clock className="size-4" />
                    {classCard.starts_at} às {classCard.ends_at}
                </span>
                <span className="flex items-center gap-1.5">
                    <Users className="size-4" />
                    {classCard.available_spots} de {classCard.max_students}{' '}
                    vagas
                </span>
            </div>
        </Link>
    );
}
