import { useForm } from '@inertiajs/react';
import { MailCheck } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
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
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { store } from '@/routes/users';
import type { Department, StudentGroup, UserRole } from './types';
import { USER_ROLES } from './types';

const SELECT_CLASS =
    'w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900';

const INITIAL_DATA = {
    name: '',
    email: '',
    role: 'student' as UserRole,
    department_id: '',
    employee_number: '',
    student_group_id: '',
    student_number: '',
    phone: '',
};

type ProvisionFormData = typeof INITIAL_DATA;

function toPayload(data: ProvisionFormData) {
    const optionalId = (value: string) => (value ? Number(value) : null);

    return {
        name: data.name,
        email: data.email,
        role: data.role,
        teacher_profile:
            data.role === 'teacher'
                ? {
                      department_id: optionalId(data.department_id),
                      employee_number: data.employee_number || null,
                      phone: data.phone || null,
                  }
                : null,
        student_profile:
            data.role === 'student'
                ? {
                      student_group_id: optionalId(data.student_group_id),
                      student_number: data.student_number || null,
                      phone: data.phone || null,
                  }
                : null,
    };
}

interface ProvisionUserDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    departments: Department[];
    studentGroups: StudentGroup[];
}

export function ProvisionUserDialog({
    open,
    onOpenChange,
    departments,
    studentGroups,
}: ProvisionUserDialogProps) {
    const { t } = useTranslation();
    const form = useForm(INITIAL_DATA);
    const errors = form.errors as Record<string, string | undefined>;
    const profileKey =
        form.data.role === 'teacher' ? 'teacher_profile' : 'student_profile';

    const handleOpenChange = (next: boolean) => {
        if (!next) {
            form.reset();
            form.clearErrors();
        }
        onOpenChange(next);
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.transform(toPayload);
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => handleOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-[560px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>{t('users.dialog_title')}</DialogTitle>
                        <DialogDescription>
                            {t('users.dialog_desc')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="user_name">
                                {t('users.dialog_name')}
                            </Label>
                            <Input
                                id="user_name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder={t('users.dialog_name_placeholder')}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="user_email">
                                    {t('users.dialog_email')}
                                </Label>
                                <Input
                                    id="user_email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) =>
                                        form.setData('email', e.target.value)
                                    }
                                    placeholder={t(
                                        'users.dialog_email_placeholder',
                                    )}
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="user_role">
                                    {t('users.dialog_role')}
                                </Label>
                                <select
                                    id="user_role"
                                    value={form.data.role}
                                    onChange={(e) =>
                                        form.setData(
                                            'role',
                                            e.target.value as UserRole,
                                        )
                                    }
                                    className={SELECT_CLASS}
                                >
                                    {USER_ROLES.map((role) => (
                                        <option key={role} value={role}>
                                            {t(`users.role_${role}`)}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.role} />
                            </div>
                        </div>

                        {form.data.role === 'teacher' && (
                            <fieldset className="grid gap-3 rounded-md border border-neutral-200 p-3 dark:border-neutral-800">
                                <legend className="px-1 text-xs font-semibold text-neutral-500">
                                    {t('users.dialog_teacher_section')}
                                </legend>
                                <div className="grid gap-2">
                                    <Label htmlFor="user_department">
                                        {t('users.dialog_department')}
                                    </Label>
                                    <select
                                        id="user_department"
                                        value={form.data.department_id}
                                        onChange={(e) =>
                                            form.setData(
                                                'department_id',
                                                e.target.value,
                                            )
                                        }
                                        className={SELECT_CLASS}
                                    >
                                        <option value="">
                                            {t('users.dialog_no_department')}
                                        </option>
                                        {departments.map((d) => (
                                            <option key={d.id} value={d.id}>
                                                {d.code} - {d.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={
                                            errors[
                                                'teacher_profile.department_id'
                                            ]
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="user_employee_number">
                                        {t('users.dialog_employee_number')}
                                    </Label>
                                    <Input
                                        id="user_employee_number"
                                        value={form.data.employee_number}
                                        onChange={(e) =>
                                            form.setData(
                                                'employee_number',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            errors[
                                                'teacher_profile.employee_number'
                                            ]
                                        }
                                    />
                                </div>
                            </fieldset>
                        )}

                        {form.data.role === 'student' && (
                            <fieldset className="grid gap-3 rounded-md border border-neutral-200 p-3 dark:border-neutral-800">
                                <legend className="px-1 text-xs font-semibold text-neutral-500">
                                    {t('users.dialog_student_section')}
                                </legend>
                                <div className="grid gap-2">
                                    <Label htmlFor="user_group">
                                        {t('users.dialog_student_group')}
                                    </Label>
                                    <select
                                        id="user_group"
                                        value={form.data.student_group_id}
                                        onChange={(e) =>
                                            form.setData(
                                                'student_group_id',
                                                e.target.value,
                                            )
                                        }
                                        className={SELECT_CLASS}
                                    >
                                        <option value="">
                                            {t('users.dialog_no_group')}
                                        </option>
                                        {studentGroups.map((g) => (
                                            <option key={g.id} value={g.id}>
                                                {g.name} ({g.academic_year})
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={
                                            errors[
                                                'student_profile.student_group_id'
                                            ]
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="user_student_number">
                                        {t('users.dialog_student_number')}
                                    </Label>
                                    <Input
                                        id="user_student_number"
                                        value={form.data.student_number}
                                        onChange={(e) =>
                                            form.setData(
                                                'student_number',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            errors[
                                                'student_profile.student_number'
                                            ]
                                        }
                                    />
                                </div>
                            </fieldset>
                        )}

                        {(form.data.role === 'teacher' ||
                            form.data.role === 'student') && (
                            <div className="grid gap-2">
                                <Label htmlFor="user_phone">
                                    {t('users.dialog_phone')}
                                </Label>
                                <Input
                                    id="user_phone"
                                    type="tel"
                                    value={form.data.phone}
                                    onChange={(e) =>
                                        form.setData('phone', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors[`${profileKey}.phone`]}
                                />
                            </div>
                        )}

                        <p className="flex items-start gap-2 rounded-md bg-sky-50 p-3 text-xs text-sky-800 dark:bg-sky-950/30 dark:text-sky-300">
                            <MailCheck className="mt-0.5 size-4 shrink-0" />
                            {t('users.dialog_invitation_notice')}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {t('users.dialog_submit')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
