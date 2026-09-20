import { Head, useForm } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import AuthLayout from '@/layouts/auth-layout';
import { verify } from '@/routes/two-factor';

export default function TwoFactorChallenge() {
    const [useRecoveryCode, setUseRecoveryCode] = useState(false);

    const { data, setData, post, errors, processing, reset } = useForm({
        code: '',
        recovery_code: '',
    });

    const onSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(verify().url, {
            onError: () => reset('code', 'recovery_code'),
        });
    };

    const toggleMode = () => {
        reset('code', 'recovery_code');
        setUseRecoveryCode((previous) => !previous);
    };

    return (
        <AuthLayout
            title="Two-factor authentication"
            description="Confirm it is you"
        >
            <Head title="Two-factor authentication" />

            <div className="mx-auto flex h-full max-w-sm flex-col items-center justify-center gap-1">
                <h1 className="mt-1 text-xl font-bold">
                    Two-factor authentication
                </h1>
                <p className="text-center text-sm text-muted-foreground">
                    {useRecoveryCode
                        ? 'Enter one of your recovery codes.'
                        : 'Enter the 6-digit code from your authenticator app.'}
                </p>

                <form
                    className="mt-4 flex w-full flex-col gap-4"
                    onSubmit={onSubmit}
                >
                    {useRecoveryCode ? (
                        <div className="flex flex-col">
                            <Label htmlFor="recovery_code" className="mb-1.5">
                                Recovery code
                            </Label>
                            <Input
                                id="recovery_code"
                                autoFocus
                                autoComplete="one-time-code"
                                value={data.recovery_code}
                                onChange={(e) =>
                                    setData('recovery_code', e.target.value)
                                }
                                disabled={processing}
                            />
                            <InputError
                                message={errors?.recovery_code}
                                className="mt-1"
                            />
                        </div>
                    ) : (
                        <div className="flex flex-col items-center gap-2">
                            <InputOTP
                                id="code"
                                name="code"
                                maxLength={OTP_MAX_LENGTH}
                                value={data.code}
                                onChange={(value) => setData('code', value)}
                                disabled={processing}
                                pattern={REGEXP_ONLY_DIGITS}
                                autoFocus
                            >
                                <InputOTPGroup>
                                    {Array.from(
                                        { length: OTP_MAX_LENGTH },
                                        (_, index) => (
                                            <InputOTPSlot
                                                key={index}
                                                index={index}
                                            />
                                        ),
                                    )}
                                </InputOTPGroup>
                            </InputOTP>
                            <InputError message={errors?.code} />
                        </div>
                    )}

                    <Button type="submit" disabled={processing}>
                        {processing && (
                            <LoaderCircle className="size-4 animate-spin" />
                        )}
                        Continue
                    </Button>

                    <button
                        type="button"
                        onClick={toggleMode}
                        className="text-center text-sm text-muted-foreground underline underline-offset-4"
                    >
                        {useRecoveryCode
                            ? 'Use an authenticator code instead'
                            : 'Lost your device? Use a recovery code'}
                    </button>
                </form>
            </div>
        </AuthLayout>
    );
}
