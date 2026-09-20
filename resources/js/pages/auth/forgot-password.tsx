import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { email as sendResetLink } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, errors, processing } = useForm({ email: '' });

    const onSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(sendResetLink().url);
    };

    return (
        <AuthLayout
            title="Forgot password"
            description="We will email you a link to choose a new one"
        >
            <Head title="Forgot password" />

            <div className="mx-auto flex h-full max-w-sm flex-col items-center justify-center gap-1">
                <h1 className="mt-1 text-xl font-bold">Forgot your password?</h1>
                <p className="text-center text-sm text-muted-foreground">
                    Enter your email address and we will send you a link to
                    choose a new password.
                </p>

                {status && (
                    <p className="mt-3 text-center text-sm font-medium text-emerald-600">
                        {status}
                    </p>
                )}

                <form className="mt-2 flex w-full flex-col gap-4" onSubmit={onSubmit}>
                    <div className="flex flex-col">
                        <Label htmlFor="email" className="mb-1.5">
                            Email address
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autoFocus
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            disabled={processing}
                        />
                        <InputError message={errors?.email} className="mt-1" />
                    </div>

                    <Button type="submit" disabled={processing}>
                        {processing && <LoaderCircle className="size-4 animate-spin" />}
                        Email password reset link
                    </Button>

                    <p className="text-center text-sm text-muted-foreground">
                        Remembered it?{' '}
                        <a href={login.url()} className="underline underline-offset-4">
                            Back to sign in
                        </a>
                    </p>
                </form>
            </div>
        </AuthLayout>
    );
}
