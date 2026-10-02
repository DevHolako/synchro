import { Check, Undo2 } from 'lucide-react';
import { memo } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import type { RoomCandidate } from './types';

interface RoomCheckInRowProps {
    candidate: RoomCandidate;
    open: boolean;
    busy: boolean;
    onCheckIn: (candidateId: number) => void;
    onUndo: (candidateId: number) => void;
}

export const RoomCheckInRow = memo(function RoomCheckInRow({
    candidate,
    open,
    busy,
    onCheckIn,
    onUndo,
}: RoomCheckInRowProps) {
    const { t } = useTranslation();
    const present = candidate.checked_in_at !== null;

    return (
        <li className="flex items-center gap-3 px-4 py-3">
            <span className="w-8 text-right font-mono text-sm text-neutral-500">
                {candidate.seat}
            </span>
            <div className="min-w-0 flex-1">
                <div className="truncate font-medium">{candidate.name}</div>
                <div className="text-xs text-neutral-500">
                    {present
                        ? t('check_in.present_since', {
                              time: candidate.checked_in_at ?? '',
                          })
                        : (candidate.student_number ?? '—')}
                </div>
            </div>
            {present ? (
                <>
                    <Check
                        className="size-5 text-emerald-600"
                        aria-label={t('check_in.present')}
                    />
                    {open ? (
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={t('check_in.undo')}
                            disabled={busy}
                            onClick={() => onUndo(candidate.id)}
                        >
                            <Undo2 className="size-4" />
                        </Button>
                    ) : null}
                </>
            ) : open ? (
                <Button
                    size="sm"
                    variant="outline"
                    disabled={busy}
                    onClick={() => onCheckIn(candidate.id)}
                >
                    {t('check_in.mark_present')}
                </Button>
            ) : null}
        </li>
    );
});
