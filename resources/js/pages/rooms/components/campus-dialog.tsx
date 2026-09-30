import { useForm } from '@inertiajs/react';
import React from 'react';
import { toast } from 'sonner';
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

interface CampusDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function CampusDialog({ open, onOpenChange }: CampusDialogProps) {
    const form = useForm({
        name: '',
        code: '',
        city: '',
        address: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/campuses', {
            onSuccess: () => {
                onOpenChange(false);
                form.reset();
                toast.success('Campus created successfully.');
            },
            onError: (errors) => {
                const first = Object.values(errors)[0];
                if (first) toast.error(first as string);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Add Campus</DialogTitle>
                        <DialogDescription>
                            Register a new institutional campus (e.g. Casablanca, Rabat).
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-3 py-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="dialog_campus_name">Campus Name *</Label>
                            <Input
                                id="dialog_campus_name"
                                placeholder="e.g. Campus Casablanca"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="dialog_campus_code">Code (Unique) *</Label>
                                <Input
                                    id="dialog_campus_code"
                                    placeholder="e.g. CASA"
                                    value={form.data.code}
                                    onChange={(e) => form.setData('code', e.target.value.toUpperCase())}
                                    required
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="dialog_campus_city">City</Label>
                                <Input
                                    id="dialog_campus_city"
                                    placeholder="e.g. Casablanca"
                                    value={form.data.city}
                                    onChange={(e) => form.setData('city', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="dialog_campus_address">Address</Label>
                            <Input
                                id="dialog_campus_address"
                                placeholder="e.g. Boulevard Bir Anzarane"
                                value={form.data.address}
                                onChange={(e) => form.setData('address', e.target.value)}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving...' : 'Create Campus'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
