import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';

type Props = {
    name: string;
    email: string;
    expiresAt: string;
    activateUrl: string;
    passwordRules: string;
};

export default function AcceptInvitation({
    name,
    email,
    expiresAt,
    activateUrl,
    passwordRules,
}: Props) {
    const { t, locale } = useTranslation();
    const form = useForm({ password: '', password_confirmation: '' });

    useEffect(() => {
        setLayoutProps({
            title: t('invitation.title'),
            description: t('invitation.description', { name }),
        });
    }, [t, name]);

    const expiryDate = new Intl.DateTimeFormat(locale, {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(expiresAt));

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.post(activateUrl, {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title={t('invitation.title')} />

            <form onSubmit={handleSubmit} className="grid gap-6">
                <div className="grid gap-2">
                    <Label htmlFor="email">{t('invitation.email')}</Label>
                    <Input
                        id="email"
                        type="email"
                        value={email}
                        autoComplete="username"
                        readOnly
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">{t('invitation.password')}</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        autoComplete="new-password"
                        value={form.data.password}
                        onChange={(e) =>
                            form.setData('password', e.target.value)
                        }
                        placeholder={t('invitation.password_placeholder')}
                        passwordrules={passwordRules}
                        autoFocus
                        required
                    />
                    <InputError message={form.errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">
                        {t('invitation.confirm_password')}
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autoComplete="new-password"
                        value={form.data.password_confirmation}
                        onChange={(e) =>
                            form.setData(
                                'password_confirmation',
                                e.target.value,
                            )
                        }
                        placeholder={t(
                            'invitation.confirm_password_placeholder',
                        )}
                        passwordrules={passwordRules}
                        required
                    />
                    <InputError message={form.errors.password_confirmation} />
                </div>

                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                    data-test="activate-account-button"
                >
                    {form.processing && <Spinner />}
                    {t('invitation.submit')}
                </Button>

                <p className="text-center text-xs text-muted-foreground">
                    {t('invitation.expires_notice', { date: expiryDate })}
                </p>
            </form>
        </>
    );
}
