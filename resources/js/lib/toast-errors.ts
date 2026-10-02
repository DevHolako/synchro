import { toast } from 'sonner';

/** Shows each validation error of a refused visit as an error toast. */
export function toastErrors(errors: Record<string, string>): void {
    Object.values(errors).forEach((message) => toast.error(message));
}
