import { Link } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import type { ManagedUser, PaginatedUsers } from './types';
import { UserRow } from './user-row';

interface UsersTableProps {
    users: PaginatedUsers;
    currentUserId: number;
    canResend: boolean;
    canIssueTemporaryPassword: boolean;
    onResendInvitation: (user: ManagedUser) => void;
    onIssueTemporaryPassword: (user: ManagedUser) => void;
    onResetFilters: () => void;
}

function PageLink({ href, label }: { href: string | null; label: string }) {
    if (!href) {
        return (
            <Button variant="outline" size="sm" disabled>
                {label}
            </Button>
        );
    }

    return (
        <Button variant="outline" size="sm" asChild>
            <Link href={href} preserveScroll>
                {label}
            </Link>
        </Button>
    );
}

export function UsersTable({
    users,
    currentUserId,
    canResend,
    canIssueTemporaryPassword,
    onResendInvitation,
    onIssueTemporaryPassword,
    onResetFilters,
}: UsersTableProps) {
    const { t } = useTranslation();

    if (users.data.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                <Users className="size-12 text-neutral-400" />
                <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                    {t('users.no_users_found')}
                </h3>
                <p className="mt-1 text-sm text-neutral-500">
                    {t('users.no_users_desc')}
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    className="mt-4"
                    onClick={onResetFilters}
                >
                    {t('common.reset_filters')}
                </Button>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <table className="w-full text-left text-sm">
                <thead className="border-b border-neutral-200 bg-neutral-50/50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                    <tr>
                        <th className="px-6 py-3">{t('users.col_user')}</th>
                        <th className="px-6 py-3">{t('users.col_role')}</th>
                        <th className="px-6 py-3">{t('users.col_profile')}</th>
                        <th className="px-6 py-3">{t('common.status')}</th>
                        <th className="px-6 py-3">
                            {t('users.col_invitation')}
                        </th>
                        <th className="px-6 py-3 text-right">
                            {t('common.actions')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {users.data.map((user) => (
                        <UserRow
                            key={user.id}
                            user={user}
                            canResend={canResend}
                            canIssueTemporaryPassword={
                                canIssueTemporaryPassword &&
                                user.id !== currentUserId
                            }
                            onResendInvitation={onResendInvitation}
                            onIssueTemporaryPassword={onIssueTemporaryPassword}
                        />
                    ))}
                </tbody>
            </table>

            <div className="flex items-center justify-between border-t border-neutral-200 px-6 py-3 text-xs text-neutral-500 dark:border-neutral-800">
                <span>
                    {t('users.pagination_summary', {
                        from: users.from ?? 0,
                        to: users.to ?? 0,
                        total: users.total,
                    })}
                </span>
                <div className="flex gap-2">
                    <PageLink
                        href={users.prev_page_url}
                        label={t('users.previous')}
                    />
                    <PageLink
                        href={users.next_page_url}
                        label={t('users.next')}
                    />
                </div>
            </div>
        </div>
    );
}
