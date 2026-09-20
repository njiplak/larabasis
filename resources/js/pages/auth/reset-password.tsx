import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { PasswordInput } from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { store } from '@/routes/password';

type Props = {
    token: string;
    email: string;
};

export default function ResetPassword({ token, email }: Props) {
    const { data, setData, post, errors, processing } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const onSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <AuthLayout title="Reset password" description="Choose a new password">
            <Head title="Reset password" />

            <div className="mx-auto flex h-full max-w-sm flex-col items-center justify-center gap-1">
                <h1 className="mt-1 text-xl font-bold">Choose a new password</h1>

                <form className="mt-2 flex w-full flex-col gap-4" onSubmit={onSubmit}>
                    <div className="flex flex-col">
                        <Label htmlFor="email" className="mb-1.5">
                            Email address
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            readOnly
                            autoComplete="username"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <InputError message={errors?.email} className="mt-1" />
                    </div>

                    <div className="flex flex-col">
                        <Label htmlFor="password" className="mb-1.5">
                            New password
                        </Label>
                        <PasswordInput
                            id="password"
                            required
                            autoFocus
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            disabled={processing}
                        />
                        <InputError message={errors?.password} className="mt-1" />
                    </div>

                    <div className="flex flex-col">
                        <Label htmlFor="password_confirmation" className="mb-1.5">
                            Confirm password
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            required
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            disabled={processing}
                        />
                        <InputError message={errors?.password_confirmation} className="mt-1" />
                    </div>

                    <Button type="submit" disabled={processing}>
                        {processing && <LoaderCircle className="size-4 animate-spin" />}
                        Reset password
                    </Button>
                </form>
            </div>
        </AuthLayout>
    );
}
