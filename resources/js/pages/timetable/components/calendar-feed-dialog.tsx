import { router } from '@inertiajs/react';
import { Check, Copy, ExternalLink } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useClipboard } from '@/hooks/use-clipboard';
import { useTranslation } from '@/i18n/LanguageContext';
import { destroy, store } from '@/routes/calendar-feed';

export interface CalendarFeedLinks {
    https: string;
    webcal: string;
}

interface CalendarFeedDialogProps {
    feed: CalendarFeedLinks | null;
    onClose: () => void;
}

const RELOAD = {
    preserveScroll: true,
    preserveState: true,
    only: ['calendarFeed', 'flash'],
};

function CopyField({
    id,
    label,
    value,
}: {
    id: string;
    label: string;
    value: string;
}) {
    const { t } = useTranslation();
    const [copied, copy] = useClipboard();

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <div className="flex gap-2">
                <Input
                    id={id}
                    value={value}
                    readOnly
                    className="font-mono text-xs"
                />
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => copy(value)}
                    aria-label={t('calendar_feed.copy')}
                >
                    {copied === value ? (
                        <Check className="size-4" />
                    ) : (
                        <Copy className="size-4" />
                    )}
                    {copied === value
                        ? t('calendar_feed.copied')
                        : t('calendar_feed.copy')}
                </Button>
            </div>
        </div>
    );
}

/** The user's private iCal subscription link (ADR 0010). */
export function CalendarFeedDialog({ feed, onClose }: CalendarFeedDialogProps) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);
    const busy = {
        onStart: () => setProcessing(true),
        onFinish: () => setProcessing(false),
    };

    const issue = () => router.post(store.url(), {}, { ...RELOAD, ...busy });
    const revoke = () => router.delete(destroy.url(), { ...RELOAD, ...busy });

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{t('calendar_feed.title')}</DialogTitle>
                    <DialogDescription>
                        {t('calendar_feed.description')}
                    </DialogDescription>
                </DialogHeader>

                <p className="text-xs text-amber-700 dark:text-amber-400">
                    {t('calendar_feed.private_warning')}
                </p>

                {feed ? (
                    <div className="grid gap-4">
                        <CopyField
                            id="calendar_feed_webcal"
                            label={t('calendar_feed.webcal_label')}
                            value={feed.webcal}
                        />
                        <CopyField
                            id="calendar_feed_https"
                            label={t('calendar_feed.https_label')}
                            value={feed.https}
                        />
                        <Button
                            asChild
                            variant="outline"
                            className="justify-self-start"
                        >
                            <a href={feed.webcal}>
                                <ExternalLink className="size-4" />
                                {t('calendar_feed.open_in_app')}
                            </a>
                        </Button>
                        <p className="text-xs text-neutral-500">
                            {t('calendar_feed.regenerate_hint')}
                        </p>
                    </div>
                ) : null}

                <DialogFooter className="gap-2">
                    {feed ? (
                        <>
                            <Button
                                type="button"
                                variant="outline"
                                className="text-red-700 dark:text-red-400"
                                onClick={revoke}
                                disabled={processing}
                            >
                                {t('calendar_feed.revoke')}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={issue}
                                disabled={processing}
                            >
                                {t('calendar_feed.regenerate')}
                            </Button>
                        </>
                    ) : (
                        <Button
                            type="button"
                            onClick={issue}
                            disabled={processing}
                        >
                            {t('calendar_feed.create')}
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
