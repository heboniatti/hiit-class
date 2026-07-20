import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, House, Target, Ticket } from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { dashboard, goals, myPlan } from '@/routes';
import { index as classesIndex } from '@/routes/classes';
import type { NavItem } from '@/types';

const items: NavItem[] = [
    { title: 'Início', href: dashboard(), icon: House },
    { title: 'Aulas', href: classesIndex(), icon: CalendarDays },
    { title: 'Metas', href: goals(), icon: Target },
    { title: 'Meu plano', href: myPlan(), icon: Ticket },
];

/**
 * Bottom navigation for students on a phone.
 *
 * Deliberately not the hamburger sheet: this audience is not comfortable with
 * technology, and a menu that hides itself behind an icon is the single biggest
 * thing they fail to find. Four destinations, always visible, icon plus label.
 */
export default function StudentBottomNav() {
    const { isCurrentUrl } = useCurrentUrl();
    const isStudent = usePage().props.auth.user.role === 'student';

    if (!isStudent) {
        return null;
    }

    return (
        <nav
            aria-label="Navegação principal"
            className="fixed inset-x-0 bottom-0 z-40 border-t bg-background pb-[env(safe-area-inset-bottom)] md:hidden"
        >
            <ul className="grid grid-cols-4">
                {items.map((item) => {
                    const active = isCurrentUrl(item.href);

                    return (
                        <li key={item.title}>
                            <Link
                                href={item.href}
                                className={cn(
                                    'flex min-h-16 flex-col items-center justify-center gap-1 px-1 py-2 text-xs font-medium transition-colors',
                                    active
                                        ? 'text-primary'
                                        : 'text-muted-foreground',
                                )}
                                aria-current={active ? 'page' : undefined}
                            >
                                {item.icon && <item.icon className="size-6" />}
                                <span>{item.title}</span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
