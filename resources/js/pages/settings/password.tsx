import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useRef } from 'react';

import InputError from '@/components/input-error';
import { PasswordInput } from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { update } from '@/routes/user-password';

export default function Password({ status }: { status?: string }) {
    const currentPasswordInput = useRef<HTMLInputElement>(null);
    const passwordInput = useRef<HTMLInputElement>(null);

    const { data, setData, put, errors, processing, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const onSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        put(update().url, {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (formErrors) => {
                if (formErrors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }
                if (formErrors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <>
            <Head title="Password" />

            <section className="space-y-4">
                <header>
                    <h2 className="text-base font-semibold">Update password</h2>
                    <p className="text-sm text-muted-foreground">
                        Use a long, random password to keep your account secure.
                    </p>
                </header>

                <form onSubmit={onSubmit} className="space-y-4">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="current_password">Current password</Label>
                        <PasswordInput
                            id="current_password"
                            ref={currentPasswordInput}
                            autoComplete="current-password"
                            value={data.current_password}
                            onChange={(e) => setData('current_password', e.target.value)}
                        />
                        <InputError message={errors?.current_password} />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="password">New password</Label>
                        <PasswordInput
                            id="password"
                            ref={passwordInput}
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors?.password} />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="password_confirmation">Confirm new password</Label>
                        <PasswordInput
                            id="password_confirmation"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                        <InputError message={errors?.password_confirmation} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Save
                        </Button>
                        {status && (
                            <p className="text-sm text-muted-foreground">{status}</p>
                        )}
                    </div>
                </form>
            </section>
        </>
    );
}

Password.layout = (page: React.ReactNode) => (
    <AppLayout>
        <SettingsLayout>{page}</SettingsLayout>
    </AppLayout>
);
