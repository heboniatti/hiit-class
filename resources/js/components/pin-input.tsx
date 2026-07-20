import { Eye, EyeOff } from 'lucide-react';
import type { ComponentProps, Ref } from 'react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/**
 * A six digit PIN field.
 *
 * Deliberately one field rather than six segmented boxes: this audience is not
 * comfortable with technology, and a single field behaves like the password
 * field they already know, pastes cleanly and reads correctly to screen
 * readers. Segmented inputs auto-advance, which is easy to fight with.
 */
export default function PinInput({
    className,
    ref,
    ...props
}: Omit<ComponentProps<'input'>, 'type' | 'inputMode' | 'maxLength'> & {
    ref?: Ref<HTMLInputElement>;
}) {
    const [showPin, setShowPin] = useState(false);

    return (
        <div className="relative">
            <Input
                type={showPin ? 'text' : 'password'}
                inputMode="numeric"
                pattern="[0-9]*"
                maxLength={6}
                autoComplete="one-time-code"
                placeholder="••••••"
                className={cn(
                    'h-12 pr-12 text-center text-xl tracking-[0.4em]',
                    className,
                )}
                ref={ref}
                {...props}
            />
            <button
                type="button"
                onClick={() => setShowPin((prev) => !prev)}
                className="absolute inset-y-0 right-0 flex items-center rounded-r-md px-3 text-muted-foreground hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring focus-visible:outline-none"
                aria-label={showPin ? 'Ocultar PIN' : 'Mostrar PIN'}
                tabIndex={-1}
            >
                {showPin ? (
                    <EyeOff className="size-5" />
                ) : (
                    <Eye className="size-5" />
                )}
            </button>
        </div>
    );
}
