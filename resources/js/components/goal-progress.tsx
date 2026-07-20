import { cn } from '@/lib/utils';

/**
 * A goal's progress bar.
 *
 * Shows the student's own distance travelled and nothing else — there is no
 * comparison to anyone, by design.
 */
export default function GoalProgress({
    current,
    target,
    percent,
    unit,
    isComplete,
    className,
}: {
    current: number;
    target: number;
    percent: number;
    unit: string;
    isComplete: boolean;
    className?: string;
}) {
    return (
        <div className={cn('space-y-2', className)}>
            <div className="flex items-baseline justify-between gap-2 text-sm">
                <span className="font-medium">
                    {current} de {target} {unit}
                </span>
                <span className="text-muted-foreground">{percent}%</span>
            </div>

            <div
                role="progressbar"
                aria-valuenow={percent}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label={`${percent}% concluído`}
                className="h-3 w-full overflow-hidden rounded-full bg-muted"
            >
                <div
                    className={cn(
                        'h-full rounded-full transition-all',
                        isComplete ? 'bg-primary' : 'bg-primary/70',
                    )}
                    style={{ width: `${Math.max(percent, 2)}%` }}
                />
            </div>
        </div>
    );
}
