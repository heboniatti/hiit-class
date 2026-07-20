import type { ComponentProps, Ref } from 'react';
import { Input } from '@/components/ui/input';
import { formatPhone } from '@/lib/phone';

type PhoneInputEvent = Parameters<
    NonNullable<ComponentProps<'input'>['onInput']>
>[0];

/**
 * A phone field that masks itself as the user types.
 *
 * Stays uncontrolled, like every other input in this project: the value is
 * rewritten in place on each keystroke rather than held in state. The caret
 * lands at the end after each edit, which is where someone typing a number
 * left to right expects it.
 */
export default function PhoneInput({
    className,
    defaultValue,
    onInput,
    ref,
    ...props
}: Omit<ComponentProps<'input'>, 'type' | 'inputMode'> & {
    ref?: Ref<HTMLInputElement>;
}) {
    const handleInput = (event: PhoneInputEvent) => {
        event.currentTarget.value = formatPhone(event.currentTarget.value);

        onInput?.(event);
    };

    return (
        <Input
            type="tel"
            inputMode="tel"
            autoComplete="tel"
            // (11) 99999-9999 is 15 characters.
            maxLength={15}
            placeholder="(11) 99999-9999"
            defaultValue={formatPhone(String(defaultValue ?? ''))}
            onInput={handleInput}
            className={className}
            ref={ref}
            {...props}
        />
    );
}
