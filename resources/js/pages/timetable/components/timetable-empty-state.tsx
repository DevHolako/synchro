import { CalendarSearch } from 'lucide-react';

interface TimetableEmptyStateProps {
    title: string;
    description: string;
}

export function TimetableEmptyState({
    title,
    description,
}: TimetableEmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-neutral-300 bg-white px-6 py-16 text-center dark:border-neutral-700 dark:bg-neutral-900">
            <CalendarSearch className="size-8 text-neutral-400" />
            <p className="font-medium text-neutral-900 dark:text-neutral-100">
                {title}
            </p>
            <p className="max-w-sm text-sm text-neutral-500 dark:text-neutral-400">
                {description}
            </p>
        </div>
    );
}
