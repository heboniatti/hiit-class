import { Head } from '@inertiajs/react';
import {
    CalendarCheck,
    CircleCheck,
    Flame,
    HeartHandshake,
    Lock,
    Medal,
    Sparkles,
    Target,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import GoalProgress from '@/components/goal-progress';
import { cn } from '@/lib/utils';
import { dashboard, goals } from '@/routes';

type GoalCard = {
    id: number;
    title: string;
    description: string | null;
    unit: string;
    ends_on: string;
    current: number;
    target: number;
    percent: number;
    is_complete: boolean;
    encouragement?: string;
};

type AchievementCard = {
    id: number;
    name: string;
    description: string;
    icon: string;
    is_unlocked: boolean;
    unlocked_at: string | null;
};

type Props = {
    streak: number;
    message: string;
    personalGoals: GoalCard[];
    groupGoals: GoalCard[];
    achievements: AchievementCard[];
};

const ICONS: Record<string, LucideIcon> = {
    sparkles: Sparkles,
    flame: Flame,
    medal: Medal,
    'calendar-check': CalendarCheck,
    'heart-handshake': HeartHandshake,
    target: Target,
    users: Users,
};

export default function StudentGoals({
    streak,
    message,
    personalGoals,
    groupGoals,
    achievements,
}: Props) {
    const unlockedCount = achievements.filter((a) => a.is_unlocked).length;

    return (
        <>
            <Head title="Metas e conquistas" />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <header>
                    <h1 className="text-2xl font-bold">Metas e conquistas</h1>
                    <p className="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                        <Sparkles className="size-4 shrink-0 text-primary" />
                        {message}
                    </p>
                </header>

                <section className="flex items-center gap-4 rounded-xl border bg-card p-4">
                    <div className="flex size-14 shrink-0 items-center justify-center rounded-full bg-primary/10">
                        <Flame className="size-7 text-primary" />
                    </div>
                    <div>
                        <p className="text-2xl leading-none font-bold">
                            {streak}{' '}
                            <span className="text-base font-normal">
                                {streak === 1 ? 'semana' : 'semanas'}
                            </span>
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {streak === 0
                                ? 'Sua sequência começa no próximo treino.'
                                : 'seguidas treinando. Continue assim!'}
                        </p>
                    </div>
                </section>

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">Suas metas</h2>

                    {personalGoals.length === 0 ? (
                        <EmptyState
                            icon={Target}
                            title="Nenhuma meta ativa"
                            description="Converse com o professor para definir uma meta pessoal."
                        />
                    ) : (
                        <ul className="space-y-3">
                            {personalGoals.map((goal) => (
                                <li
                                    key={goal.id}
                                    className="space-y-3 rounded-xl border bg-card p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="font-semibold">
                                                {goal.title}
                                            </p>
                                            {goal.description && (
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {goal.description}
                                                </p>
                                            )}
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                até {goal.ends_on}
                                            </p>
                                        </div>
                                        {goal.is_complete && (
                                            <CircleCheck className="size-5 shrink-0 text-primary" />
                                        )}
                                    </div>

                                    <GoalProgress
                                        current={goal.current}
                                        target={goal.target}
                                        percent={goal.percent}
                                        unit={goal.unit}
                                        isComplete={goal.is_complete}
                                    />

                                    {goal.is_complete && (
                                        <p className="rounded-lg bg-primary/10 p-3 text-sm font-medium text-primary">
                                            Meta concluída. Que jornada!
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {groupGoals.length > 0 && (
                    <section className="space-y-3">
                        <h2 className="text-lg font-semibold">
                            Metas do grupo
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Conquistas de todo mundo, somadas.
                        </p>

                        <ul className="space-y-3">
                            {groupGoals.map((goal) => (
                                <li
                                    key={goal.id}
                                    className="space-y-3 rounded-xl border bg-card p-4"
                                >
                                    <div className="flex items-start gap-3">
                                        <Users className="mt-0.5 size-5 shrink-0 text-primary" />
                                        <div className="min-w-0">
                                            <p className="font-semibold">
                                                {goal.title}
                                            </p>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                até {goal.ends_on}
                                            </p>
                                        </div>
                                    </div>

                                    <GoalProgress
                                        current={goal.current}
                                        target={goal.target}
                                        percent={goal.percent}
                                        unit={goal.unit}
                                        isComplete={goal.is_complete}
                                    />

                                    {goal.encouragement && (
                                        <p className="rounded-lg bg-primary/10 p-3 text-sm font-medium text-primary">
                                            {goal.encouragement}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">Suas conquistas</h2>
                    <p className="text-sm text-muted-foreground">
                        {unlockedCount} de {achievements.length} conquistadas.
                    </p>

                    <ul className="grid gap-3 sm:grid-cols-2">
                        {achievements.map((achievement) => {
                            const Icon = ICONS[achievement.icon] ?? Medal;

                            return (
                                <li
                                    key={achievement.id}
                                    className={cn(
                                        'flex items-start gap-3 rounded-xl border p-4',
                                        achievement.is_unlocked
                                            ? 'border-primary/30 bg-primary/5'
                                            : 'bg-card',
                                    )}
                                >
                                    <div
                                        className={cn(
                                            'flex size-10 shrink-0 items-center justify-center rounded-full',
                                            achievement.is_unlocked
                                                ? 'bg-primary/15 text-primary'
                                                : 'bg-muted text-muted-foreground',
                                        )}
                                    >
                                        {achievement.is_unlocked ? (
                                            <Icon className="size-5" />
                                        ) : (
                                            <Lock className="size-4" />
                                        )}
                                    </div>
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {achievement.name}
                                        </p>
                                        <p className="mt-0.5 text-sm text-muted-foreground">
                                            {achievement.is_unlocked
                                                ? `Conquistada em ${achievement.unlocked_at}`
                                                : achievement.description}
                                        </p>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </section>
            </div>
        </>
    );
}

StudentGoals.layout = {
    breadcrumbs: [
        { title: 'Início', href: dashboard() },
        { title: 'Metas', href: goals() },
    ],
};
