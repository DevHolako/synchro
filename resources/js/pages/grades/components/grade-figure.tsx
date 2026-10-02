/** One figure of a grade sheet: a label over its value. */
export function GradeFigure({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div className="rounded-lg border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
            <div className="text-xs text-neutral-500">{label}</div>
            <div className="text-xl font-bold">{value}</div>
        </div>
    );
}
