import type { LucideIcon } from 'lucide-react';

export default function EmptyState({
    icon: Icon,
    title,
    description,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
}) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-10 text-center">
            <Icon className="size-8 text-muted-foreground" />
            <p className="font-medium">{title}</p>
            <p className="max-w-sm text-sm text-muted-foreground">
                {description}
            </p>
        </div>
    );
}
