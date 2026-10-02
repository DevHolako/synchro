import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/LanguageContext';

interface UserNameFieldsProps {
    /** Students get their official surname and given name; everyone else a single name. */
    official: boolean;
    name: string;
    lastName: string;
    firstName: string;
    errors: Record<string, string | undefined>;
    onChange: (
        field: 'name' | 'last_name' | 'first_name',
        value: string,
    ) => void;
}

export function UserNameFields({
    official,
    name,
    lastName,
    firstName,
    errors,
    onChange,
}: UserNameFieldsProps) {
    const { t } = useTranslation();

    if (!official) {
        return (
            <div className="grid gap-2">
                <Label htmlFor="user_name">{t('users.dialog_name')}</Label>
                <Input
                    id="user_name"
                    value={name}
                    onChange={(e) => onChange('name', e.target.value)}
                    placeholder={t('users.dialog_name_placeholder')}
                    required
                />
                <InputError message={errors.name} />
            </div>
        );
    }

    return (
        <div className="grid grid-cols-2 gap-3">
            <div className="grid gap-2">
                <Label htmlFor="user_last_name">
                    {t('users.dialog_last_name')}
                </Label>
                <Input
                    id="user_last_name"
                    value={lastName}
                    onChange={(e) => onChange('last_name', e.target.value)}
                    placeholder={t('users.dialog_last_name_placeholder')}
                    required
                />
                <InputError message={errors['student_profile.last_name']} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="user_first_name">
                    {t('users.dialog_first_name')}
                </Label>
                <Input
                    id="user_first_name"
                    value={firstName}
                    onChange={(e) => onChange('first_name', e.target.value)}
                    placeholder={t('users.dialog_first_name_placeholder')}
                    required
                />
                <InputError message={errors['student_profile.first_name']} />
            </div>
        </div>
    );
}
