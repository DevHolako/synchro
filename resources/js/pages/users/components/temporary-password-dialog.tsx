import { Check, Copy } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useClipboard } from '@/hooks/use-clipboard';
import { useTranslation } from '@/i18n/LanguageContext';
import type { TemporaryPasswordFlash } from './types';

interface TemporaryPasswordDialogProps {
    credentials: TemporaryPasswordFlash | null;
    onClose: () => void;
}

export function TemporaryPasswordDialog({
    credentials,
    onClose,
}: TemporaryPasswordDialogProps) {
    const { t } = useTranslation();
    const [copiedText, copy] = useClipboard();
    const isCopied =
        credentials !== null && copiedText === credentials.password;

    return (
        <Dialog
            open={credentials !== null}
            onOpenChange={(o) => !o && onClose()}
        >
            <DialogContent className="sm:max-w-[440px]">
                <DialogHeader>
                    <DialogTitle>{t('users.temp_dialog_title')}</DialogTitle>
                    <DialogDescription>
                        {t('users.temp_dialog_desc', {
                            name: credentials?.name ?? '',
                            email: credentials?.email ?? '',
                        })}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex items-center gap-2 py-2">
                    <code className="flex-1 rounded-md border border-neutral-200 bg-neutral-50 px-3 py-2 font-mono text-sm tracking-wider select-all dark:border-neutral-800 dark:bg-neutral-900">
                        {credentials?.password}
                    </code>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            credentials && void copy(credentials.password)
                        }
                    >
                        {isCopied ? (
                            <Check className="mr-1.5 size-4" />
                        ) : (
                            <Copy className="mr-1.5 size-4" />
                        )}
                        {isCopied
                            ? t('users.temp_dialog_copied')
                            : t('users.temp_dialog_copy')}
                    </Button>
                </div>

                <DialogFooter>
                    <Button type="button" onClick={onClose}>
                        {t('users.temp_dialog_close')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
