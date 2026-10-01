import { TriangleAlert } from 'lucide-react';
import { memo } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import type { ScopePerspective, TimetableSession } from './types';

interface SessionEventContentProps {
    session: TimetableSession;
    perspective: ScopePerspective;
    timeText: string;
    compact: boolean;
}

/**
 * A session card: module code and time, then the room, groups and teacher, except the one
 * the timetable is already about.
 */
export const SessionEventContent = memo(function SessionEventContent({
    session,
    perspective,
    timeText,
    compact,
}: SessionEventContentProps) {
    const { t } = useTranslation();
    const hasOverride = session.overrides.length > 0;
    const badges = [
        perspective === 'room' ? null : session.room.name,
        perspective === 'group'
            ? null
            : session.groups
                  .map((group) => group.code ?? group.name)
                  .join(', '),
        perspective === 'teacher' ? null : session.teacher.name,
    ].filter((badge): badge is string => Boolean(badge));

    return (
        <div
            className="flex h-full min-w-0 flex-col gap-0.5 overflow-hidden px-1 py-0.5 text-xs leading-tight"
            title={session.module.name}
        >
            <div className="flex items-center gap-1 font-semibold">
                {hasOverride ? (
                    <TriangleAlert
                        className="size-3 shrink-0"
                        aria-label={t('timetable.override_badge')}
                    />
                ) : null}
                <span className="truncate">{session.module.code}</span>
            </div>
            {compact ? null : (
                <span className="truncate opacity-80">{timeText}</span>
            )}
            {compact ? null : (
                <div className="flex flex-wrap gap-1">
                    {badges.map((badge) => (
                        <span
                            key={badge}
                            className="max-w-full truncate rounded bg-black/10 px-1 dark:bg-white/15"
                        >
                            {badge}
                        </span>
                    ))}
                </div>
            )}
        </div>
    );
});
