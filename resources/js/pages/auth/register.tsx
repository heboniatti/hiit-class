import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PhoneInput from '@/components/phone-input';
import PinInput from '@/components/pin-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    return (
        <>
            <Head title="Criar conta" />

            <Form
                {...store.form()}
                resetOnError={['password', 'password_confirmation']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nome completo</Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="name"
                                placeholder="Seu nome completo"
                                className="h-12 text-base"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Telefone</Label>
                            <PhoneInput
                                id="phone"
                                name="phone"
                                required
                                tabIndex={2}
                                className="h-12 text-base"
                            />
                            <p className="text-sm text-muted-foreground">
                                Você vai usar este número para entrar.
                            </p>
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                Crie um PIN de 6 números
                            </Label>
                            <PinInput
                                id="password"
                                name="password"
                                required
                                tabIndex={3}
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Repita o PIN
                            </Label>
                            <PinInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                tabIndex={4}
                                autoComplete="new-password"
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <Button
                            type="submit"
                            className="h-12 w-full text-base"
                            tabIndex={5}
                            disabled={processing}
                            data-test="register-button"
                        >
                            {processing && <Spinner />}
                            Criar minha conta
                        </Button>

                        <p className="text-center text-sm text-muted-foreground">
                            Depois de criar sua conta, o professor libera seu
                            plano para você começar a treinar.
                        </p>

                        <p className="text-center text-sm">
                            Já tem conta?{' '}
                            <TextLink href={login()} tabIndex={6}>
                                Entrar
                            </TextLink>
                        </p>
                    </div>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Criar sua conta',
    description: 'Informe seu nome, telefone e escolha um PIN',
};
