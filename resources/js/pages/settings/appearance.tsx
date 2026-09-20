import { Head } from '@inertiajs/react';

import AppearanceToggleTab from '@/components/appearance-tabs';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

export default function Appearance() {
    return (
        <>
            <Head title="Appearance" />

            <section className="space-y-4">
                <header>
                    <h2 className="text-base font-semibold">Appearance</h2>
                    <p className="text-sm text-muted-foreground">
                        Choose how the console looks on this device.
                    </p>
                </header>

                <AppearanceToggleTab />
            </section>
        </>
    );
}

Appearance.layout = (page: React.ReactNode) => (
    <AppLayout>
        <SettingsLayout>{page}</SettingsLayout>
    </AppLayout>
);
