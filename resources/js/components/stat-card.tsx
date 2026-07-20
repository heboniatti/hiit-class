import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * One number the teacher needs at a glance. Icon plus text, never icon alone.
 */
export default function StatCard({
    icon: Icon,
    label,
    value,
    tone = 'default',
}: {
    icon: LucideIcon;
    label: string;
    value: number | string;
    tone?: 'default' | 'alert';
}) {
    return (
        <div className="rounded-xl border bg-card p-4">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Icon
                    className={cn(
                        'size-4 shrink-0',
                        tone === 'alert' && 'text-destructive',
                    )}
                />
                {label}
            </div>
            <p
                className={cn(
                    'mt-2 text-2xl font-bold',
                    tone === 'alert' && Number(value) > 0 && 'text-destructive',
                )}
            >
                {value}
            </p>
        </div>
    );
}
