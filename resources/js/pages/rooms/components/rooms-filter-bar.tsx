import { Filter, Search } from 'lucide-react';
import React, { useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/LanguageContext';
import type { Campus } from './types';

interface RoomsFilterBarProps {
    campuses: Campus[];
    searchTerm: string;
    onSearchChange: (value: string) => void;
    selectedCampus: string;
    onCampusChange: (value: string) => void;
    selectedBuilding: string;
    onBuildingChange: (value: string) => void;
    selectedStatus: string;
    onStatusChange: (value: string) => void;
    filterProjector: boolean;
    onProjectorChange: (value: boolean) => void;
    filterLab: boolean;
    onLabChange: (value: boolean) => void;
    filterComputers: boolean;
    onComputersChange: (value: boolean) => void;
    filterSound: boolean;
    onSoundChange: (value: boolean) => void;
    onApplyFilters: () => void;
    onResetFilters: () => void;
}

export function RoomsFilterBar({
    campuses,
    searchTerm,
    onSearchChange,
    selectedCampus,
    onCampusChange,
    selectedBuilding,
    onBuildingChange,
    selectedStatus,
    onStatusChange,
    filterProjector,
    onProjectorChange,
    filterLab,
    onLabChange,
    filterComputers,
    onComputersChange,
    filterSound,
    onSoundChange,
    onApplyFilters,
    onResetFilters,
}: RoomsFilterBarProps) {
    const { t } = useTranslation();

    const availableBuildings = useMemo(() => {
        if (!selectedCampus) {
            return campuses.flatMap((c) => c.buildings || []);
        }
        return (
            campuses.find((c) => c.id.toString() === selectedCampus)
                ?.buildings || []
        );
    }, [campuses, selectedCampus]);

    return (
        <Card>
            <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Filter className="size-4 text-neutral-500" />
                        <CardTitle className="text-base font-semibold">
                            {t('rooms.filter_title')}
                        </CardTitle>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={onResetFilters}
                        >
                            {t('common.reset')}
                        </Button>
                        <Button size="sm" onClick={onApplyFilters}>
                            {t('common.apply_filters')}
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium">
                            {t('common.search')}
                        </Label>
                        <div className="relative">
                            <Search className="absolute top-2.5 left-2.5 size-4 text-neutral-400" />
                            <Input
                                placeholder={t(
                                    'rooms.filter_search_placeholder',
                                )}
                                value={searchTerm}
                                onChange={(e) => onSearchChange(e.target.value)}
                                className="pl-8"
                                onKeyDown={(e) =>
                                    e.key === 'Enter' && onApplyFilters()
                                }
                            />
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium">
                            {t('academic.col_campus')}
                        </Label>
                        <select
                            value={selectedCampus}
                            onChange={(e) => onCampusChange(e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">{t('rooms.all_campuses')}</option>
                            {campuses.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name} ({c.code})
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium">
                            {t('rooms.dialog_building')}
                        </Label>
                        <select
                            value={selectedBuilding}
                            onChange={(e) => onBuildingChange(e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">{t('rooms.all_buildings')}</option>
                            {availableBuildings.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {b.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium">
                            {t('common.status')}
                        </Label>
                        <select
                            value={selectedStatus}
                            onChange={(e) => onStatusChange(e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="all">
                                {t('common.all_statuses')}
                            </option>
                            <option value="1">{t('common.active_only')}</option>
                            <option value="0">
                                {t('common.inactive_only')}
                            </option>
                        </select>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-4 pt-2">
                    <span className="text-xs font-semibold text-neutral-500 uppercase">
                        {t('rooms.equipment')}
                    </span>
                    <label className="flex cursor-pointer items-center gap-1.5 text-xs">
                        <Checkbox
                            checked={filterProjector}
                            onCheckedChange={(c) => onProjectorChange(!!c)}
                        />
                        {t('rooms.projector')}
                    </label>
                    <label className="flex cursor-pointer items-center gap-1.5 text-xs">
                        <Checkbox
                            checked={filterLab}
                            onCheckedChange={(c) => onLabChange(!!c)}
                        />
                        {t('rooms.lab')}
                    </label>
                    <label className="flex cursor-pointer items-center gap-1.5 text-xs">
                        <Checkbox
                            checked={filterComputers}
                            onCheckedChange={(c) => onComputersChange(!!c)}
                        />
                        {t('rooms.computers')}
                    </label>
                    <label className="flex cursor-pointer items-center gap-1.5 text-xs">
                        <Checkbox
                            checked={filterSound}
                            onCheckedChange={(c) => onSoundChange(!!c)}
                        />
                        {t('rooms.sound')}
                    </label>
                </div>
            </CardContent>
        </Card>
    );
}
