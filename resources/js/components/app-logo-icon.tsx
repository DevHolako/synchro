import { CalendarClock } from 'lucide-react';
import type { LucideProps } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Synchro's mark. Callers style it like a filled logo (`fill-current`), so the
 * stroke-based icon forces `fill-none` to stay an outline.
 */
export default function AppLogoIcon({ className, ...props }: LucideProps) {
    return <CalendarClock {...props} className={cn(className, 'fill-none')} />;
}
