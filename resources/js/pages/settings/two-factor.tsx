import { Form, Head } from '@inertiajs/react';
import { ShieldBan, ShieldCheck } from 'lucide-react';
import { useState } from 'react';

import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { disable, enable } from '@/routes/two-factor';

type Props = {
    twoFactorEnabled: boolean;
    requiresConfirmation: boolean;
};

export default function TwoFactor({
    twoFactorEnabled,
    requiresConfirmation,
}: Props) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const {
        qrCodeSvg,
        manualSetupKey,
        recoveryCodesList,
        clearSetupData,
        fetchSetupData,
        fetchRecoveryCodes,
        errors,
    } = useTwoFactorAuth();

    return (
        <>
            <Head title="Two-Factor Auth" />

            <section className="space-y-4">
                <header className="space-y-1">
                    <div className="flex items-center gap-2">
                        <h2 className="text-base font-semibold">
                            Two-factor authentication
                        </h2>
                        <Badge variant={twoFactorEnabled ? 'default' : 'secondary'}>
                            {twoFactorEnabled ? 'Enabled' : 'Disabled'}
                        </Badge>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Require a code from your authenticator app in addition
                        to your password when you sign in.
                    </p>
                </header>

                {twoFactorEnabled ? (
                    <div className="space-y-4">
                        <Form {...disable.form()} options={{ preserveScroll: true }}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    <ShieldBan className="size-4" />
                                    Disable two-factor authentication
                                </Button>
                            )}
                        </Form>

                        <TwoFactorRecoveryCodes
                            recoveryCodesList={recoveryCodesList}
                            fetchRecoveryCodes={fetchRecoveryCodes}
                            errors={errors}
                        />
                    </div>
                ) : (
                    <Form
                        {...enable.form()}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setIsModalOpen(true)}
                    >
                        {({ processing }) => (
                            <Button type="submit" disabled={processing}>
                                <ShieldCheck className="size-4" />
                                Enable two-factor authentication
                            </Button>
                        )}
                    </Form>
                )}
            </section>

            <TwoFactorSetupModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                requiresConfirmation={requiresConfirmation}
                twoFactorEnabled={twoFactorEnabled}
                qrCodeSvg={qrCodeSvg}
                manualSetupKey={manualSetupKey}
                clearSetupData={clearSetupData}
                fetchSetupData={fetchSetupData}
                errors={errors}
            />
        </>
    );
}

TwoFactor.layout = (page: React.ReactNode) => (
    <AppLayout>
        <SettingsLayout>{page}</SettingsLayout>
    </AppLayout>
);
