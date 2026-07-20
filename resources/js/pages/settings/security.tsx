import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PinInput from '@/components/pin-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';

export default function Security() {
    const pinInput = useRef<HTMLInputElement>(null);
    const currentPinInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Meu PIN" />

            <h1 className="sr-only">Meu PIN</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Alterar meu PIN"
                    description="Use 6 números que você lembre com facilidade e não compartilhe com ninguém"
                />

                <Form
                    {...SecurityController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                    onError={(errors) => {
                        if (errors.password) {
                            pinInput.current?.focus();
                        }

                        if (errors.current_password) {
                            currentPinInput.current?.focus();
                        }
                    }}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="current_password">
                                    PIN atual
                                </Label>

                                <PinInput
                                    id="current_password"
                                    ref={currentPinInput}
                                    name="current_password"
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                />

                                <InputError message={errors.current_password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Novo PIN</Label>

                                <PinInput
                                    id="password"
                                    ref={pinInput}
                                    name="password"
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                />

                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Repita o novo PIN
                                </Label>

                                <PinInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                />

                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-password-button"
                                >
                                    Salvar novo PIN
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Meu PIN',
            href: edit(),
        },
    ],
};
