import { KeyRound, Send } from 'lucide-react';
import { memo } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { RoleBadge, StatusBadge } from './user-badges';
import type { ManagedUser } from './types';

interface UserRowProps {
    user: ManagedUser;
    canResend: boolean;
    canIssueTemporaryPassword: boolean;
    onResendInvitation: (user: ManagedUser) => void;
    onIssueTemporaryPassword: (user: ManagedUser) => void;
}

function formatDate(value: string, locale: string): string {
    return new Intl.DateTimeFormat(locale, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function InvitationCell({ user }: { user: ManagedUser }) {
    const { t, locale } = useTranslation();
    const invitation = user.latest_invitation;

    if (user.status === 'active') {
        return user.activated_at ? (
            <span className="text-xs text-neutral-500">
                {t('users.invitation_activated', {
                    date: formatDate(user.activated_at, locale),
                })}
            </span>
        ) : null;
    }

    if (!invitation || invitation.consumed_at || invitation.revoked_at) {
        return (
            <span className="text-xs text-neutral-400 italic">
                {t('users.invitation_none')}
            </span>
        );
    }

    const isExpired = new Date(invitation.expires_at).getTime() < Date.now();
    const date = formatDate(invitation.expires_at, locale);

    return isExpired ? (
        <span className="text-xs font-medium text-red-600 dark:text-red-400">
            {t('users.invitation_expired', { date })}
        </span>
    ) : (
        <span className="text-xs text-amber-700 dark:text-amber-400">
            {t('users.invitation_expires', { date })}
        </span>
    );
}

function ProfileCell({ user }: { user: ManagedUser }) {
    const teacher = user.teacher_profile;
    const student = user.student_profile;
    const primary =
        teacher?.department?.name ?? student?.student_group?.name ?? null;
    const secondary = teacher?.employee_number ?? student?.student_number;

    if (!primary && !secondary) {
        return <span className="text-xs text-neutral-400">—</span>;
    }

    return (
        <div>
            {primary && (
                <div className="text-sm text-neutral-800 dark:text-neutral-200">
                    {primary}
                </div>
            )}
            {secondary && (
                <div className="font-mono text-[11px] text-neutral-400">
                    {secondary}
                </div>
            )}
        </div>
    );
}

export const UserRow = memo(function UserRow({
    user,
    canResend,
    canIssueTemporaryPassword,
    onResendInvitation,
    onIssueTemporaryPassword,
}: UserRowProps) {
    const { t } = useTranslation();

    return (
        <tr className="transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40">
            <td className="px-6 py-4">
                <div className="font-semibold text-neutral-900 dark:text-neutral-100">
                    {user.name}
                </div>
                <div className="font-mono text-xs text-neutral-500">
                    {user.email}
                </div>
            </td>
            <td className="px-6 py-4">
                <RoleBadge role={user.role} />
            </td>
            <td className="px-6 py-4">
                <ProfileCell user={user} />
            </td>
            <td className="px-6 py-4">
                <StatusBadge status={user.status} />
            </td>
            <td className="px-6 py-4">
                <InvitationCell user={user} />
            </td>
            <td className="px-6 py-4 text-right">
                <div className="flex items-center justify-end gap-1">
                    {canResend && user.status === 'invited' && (
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => onResendInvitation(user)}
                            title={t('users.resend_invitation')}
                            aria-label={t('users.resend_invitation')}
                        >
                            <Send className="size-4" />
                        </Button>
                    )}
                    {canIssueTemporaryPassword && (
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => onIssueTemporaryPassword(user)}
                            title={t('users.temporary_password')}
                            aria-label={t('users.temporary_password')}
                        >
                            <KeyRound className="size-4" />
                        </Button>
                    )}
                </div>
            </td>
        </tr>
    );
});
