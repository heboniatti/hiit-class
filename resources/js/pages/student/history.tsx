import { Head } from '@inertiajs/react';
import { CircleCheck, CircleMinus, History as HistoryIcon } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import { dashboard, history } from '@/routes';
import type { HistoryRow, RecentAttendance } from '@/types';

type Props = {
    history: HistoryRow[];
    attendance: RecentAttendance;
};

export default function StudentHistory({ history: rows, attendance }: Props) {
    return (
        <>
            <Head title="Histórico" />

            <div className="mx-auto w-full max-w-2xl space-y-4 p-4">
                <header>
                    <h1 className="text-2xl font-bold">Seu histórico</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {attendance.attended}{' '}
                        {attendance.attended === 1 ? 'aula' : 'aulas'}{' '}
                        concluídas nas últimas {attendance.weeks} semanas.
                    </p>
                </header>

                {rows.length === 0 ? (
                    <EmptyState
                        icon={HistoryIcon}
                        title="Seu histórico está vazio"
                        description="Depois da sua primeira aula, tudo o que você treinar aparece aqui."
                    />
                ) : (
                    <ul className="space-y-3">
                        {rows.map((row) => (
                            <li
                                key={row.id}
                                className="flex items-center gap-3 rounded-xl border bg-card p-4"
                            >
                                {row.attended ? (
                                    <CircleCheck className="size-5 shrink-0 text-primary" />
                                ) : (
                                    <CircleMinus className="size-5 shrink-0 text-muted-foreground" />
                                )}
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium">
                                        {row.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {row.date} às {row.starts_at}
                                    </p>
                                </div>
                                <span className="shrink-0 text-sm text-muted-foreground">
                                    {row.status}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

StudentHistory.layout = {
    breadcrumbs: [
        { title: 'Início', href: dashboard() },
        { title: 'Histórico', href: history() },
    ],
};
