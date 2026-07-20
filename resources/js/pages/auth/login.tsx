import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PhoneInput from '@/components/phone-input';
import PinInput from '@/components/pin-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';

type Props = {
    status?: string;
};

export default function Login({ status }: Props) {
    return (
        <>
            <Head title="Entrar" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Telefone</Label>
                            <PhoneInput
                                id="phone"
                                name="phone"
                                required
                                autoFocus
                                tabIndex={1}
                                className="h-12 text-base"
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">PIN de 6 números</Label>
                            <PinInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <Button
                            type="submit"
                            className="h-12 w-full text-base"
                            tabIndex={3}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            Entrar
                        </Button>

                        <p className="text-center text-sm text-muted-foreground">
                            Esqueceu seu PIN? Procure o professor para criar um
                            novo.
                        </p>

                        <p className="text-center text-sm">
                            Ainda não tem conta?{' '}
                            <TextLink href={register()} tabIndex={4}>
                                Criar conta
                            </TextLink>
                        </p>
                    </div>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-primary">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Entrar na sua conta',
    description: 'Use seu telefone e seu PIN de 6 números',
};
