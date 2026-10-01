import { Filter, RotateCcw, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/i18n/LanguageContext';
import type { UserFilters } from './types';
import { ACCOUNT_STATUSES, USER_ROLES } from './types';

const SELECT_CLASS =
    'w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900';

interface UserFilterBarProps {
    filters: UserFilters;
    onChange: (patch: Partial<UserFilters>) => void;
    onApply: () => void;
    onReset: () => void;
}

export function UserFilterBar({
    filters,
    onChange,
    onApply,
    onReset,
}: UserFilterBarProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-wrap items-center gap-3 rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <div className="relative min-w-[220px] flex-1">
                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                <Input
                    placeholder={t('users.filter_search_placeholder')}
                    value={filters.search}
                    onChange={(e) => onChange({ search: e.target.value })}
                    onKeyDown={(e) => e.key === 'Enter' && onApply()}
                    className="pl-9"
                />
            </div>

            <div className="w-[180px]">
                <select
                    aria-label={t('users.col_role')}
                    value={filters.role}
                    onChange={(e) =>
                        onChange({
                            role: e.target.value as UserFilters['role'],
                        })
                    }
                    className={SELECT_CLASS}
                >
                    <option value="">{t('users.all_roles')}</option>
                    {USER_ROLES.map((role) => (
                        <option key={role} value={role}>
                            {t(`users.role_${role}`)}
                        </option>
                    ))}
                </select>
            </div>

            <div className="w-[160px]">
                <select
                    aria-label={t('common.status')}
                    value={filters.status}
                    onChange={(e) =>
                        onChange({
                            status: e.target.value as UserFilters['status'],
                        })
                    }
                    className={SELECT_CLASS}
                >
                    <option value="">{t('common.all_statuses')}</option>
                    {ACCOUNT_STATUSES.map((status) => (
                        <option key={status} value={status}>
                            {t(`users.status_${status}`)}
                        </option>
                    ))}
                </select>
            </div>

            <Button variant="default" size="sm" onClick={onApply}>
                <Filter className="mr-1.5 size-4" />
                {t('common.apply_filters')}
            </Button>

            <Button variant="ghost" size="sm" onClick={onReset}>
                <RotateCcw className="mr-1.5 size-4" />
                {t('common.reset')}
            </Button>
        </div>
    );
}
