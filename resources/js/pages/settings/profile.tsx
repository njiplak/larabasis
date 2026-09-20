import { Head, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { FormResponse } from '@/lib/constant';
import { update } from '@/routes/profile';
import type { SharedData } from '@/types';

export default function Profile({ status }: { status?: string }) {
    const user = usePage<SharedData>().props.auth.user;

    const { data, setData, patch, errors, processing } = useForm({
        name: user.name,
        email: user.email,
    });

    const onSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        patch(update().url, FormResponse);
    };

    return (
        <>
            <Head title="Profile" />

            <section className="space-y-4">
                <header>
                    <h2 className="text-base font-semibold">Profile information</h2>
                    <p className="text-sm text-muted-foreground">
                        Update your name and email address.
                    </p>
                </header>

                <form onSubmit={onSubmit} className="space-y-4">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            autoComplete="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors?.name} />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            autoComplete="username"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <InputError message={errors?.email} />
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

Profile.layout = (page: React.ReactNode) => (
    <AppLayout>
        <SettingsLayout>{page}</SettingsLayout>
    </AppLayout>
);
