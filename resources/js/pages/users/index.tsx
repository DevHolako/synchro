import { Head, router, setLayoutProps, usePage } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index, resendInvitation, temporaryPassword } from '@/routes/users';
import { ProvisionUserDialog } from './components/provision-user-dialog';
import { TemporaryPasswordDialog } from './components/temporary-password-dialog';
import type {
    Department,
    ManagedUser,
    PaginatedUsers,
    StudentGroup,
    TemporaryPasswordFlash,
    UserAbilities,
    UserFilters,
    UserStats,
} from './components/types';
import { UserFilterBar } from './components/user-filter-bar';
import { UserStatsCards } from './components/user-stats';
import { UsersTable } from './components/users-table';

interface UsersIndexProps {
    users: PaginatedUsers;
    departments: Department[];
    studentGroups: StudentGroup[];
    filters: UserFilters;
    stats: UserStats;
    can: UserAbilities;
}

const EMPTY_FILTERS: UserFilters = { search: '', role: '', status: '' };

function compactFilters(filters: UserFilters) {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );
}

export default function UsersIndex({
    users,
    departments,
    studentGroups,
    filters: initialFilters,
    stats,
    can,
}: UsersIndexProps) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const [filters, setFilters] = useState<UserFilters>(initialFilters);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [credentials, setCredentials] =
        useState<TemporaryPasswordFlash | null>(null);

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.users'), href: index().url },
            ],
        });
    }, [t]);

    const applyFilters = (next: UserFilters) =>
        router.get(index.url(), compactFilters(next), { preserveState: true });

    const handleReset = () => {
        setFilters(EMPTY_FILTERS);
        applyFilters(EMPTY_FILTERS);
    };

    const handleResendInvitation = useCallback((user: ManagedUser) => {
        router.post(
            resendInvitation.url(user.id),
            {},
            { preserveScroll: true },
        );
    }, []);

    const handleIssueTemporaryPassword = useCallback(
        (user: ManagedUser) => {
            if (
                !window.confirm(
                    t('users.confirm_temporary_password', { name: user.name }),
                )
            ) {
                return;
            }

            router.post(
                temporaryPassword.url(user.id),
                {},
                {
                    preserveScroll: true,
                    onFlash: (flash) =>
                        setCredentials(
                            (flash.temporary_password as
                                | TemporaryPasswordFlash
                                | undefined) ?? null,
                        ),
                },
            );
        },
        [t],
    );

    return (
        <>
            <Head title={t('users.title')} />

            <div className="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            {t('users.title')}
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            {t('users.description')}
                        </p>
                    </div>

                    {can.provision && (
                        <Button size="sm" onClick={() => setIsDialogOpen(true)}>
                            <UserPlus className="mr-1.5 size-4" />
                            {t('users.new_user')}
                        </Button>
                    )}
                </div>

                <UserStatsCards stats={stats} />

                <UserFilterBar
                    filters={filters}
                    onChange={(patch) =>
                        setFilters((f) => ({ ...f, ...patch }))
                    }
                    onApply={() => applyFilters(filters)}
                    onReset={handleReset}
                />

                <UsersTable
                    users={users}
                    currentUserId={auth.user.id}
                    canResend={can.provision}
                    canIssueTemporaryPassword={can.issue_temporary_password}
                    onResendInvitation={handleResendInvitation}
                    onIssueTemporaryPassword={handleIssueTemporaryPassword}
                    onResetFilters={handleReset}
                />
            </div>

            <ProvisionUserDialog
                open={isDialogOpen}
                onOpenChange={setIsDialogOpen}
                departments={departments}
                studentGroups={studentGroups}
            />

            <TemporaryPasswordDialog
                credentials={credentials}
                onClose={() => setCredentials(null)}
            />
        </>
    );
}
