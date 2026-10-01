import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { login } from '@/routes';

export default function InvitationInvalid() {
    const { t } = useTranslation();

    useEffect(() => {
        setLayoutProps({
            title: t('invitation.invalid_title'),
            description: t('invitation.invalid_description'),
        });
    }, [t]);

    return (
        <>
            <Head title={t('invitation.invalid_title')} />

            <div className="grid gap-6 text-center">
                <p className="text-sm text-muted-foreground">
                    {t('invitation.invalid_help')}
                </p>
                <Button asChild variant="outline" className="w-full">
                    <Link href={login()}>{t('invitation.back_to_login')}</Link>
                </Button>
            </div>
        </>
    );
}
