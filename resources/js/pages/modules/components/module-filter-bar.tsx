import { Filter, RotateCcw, Search } from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Program, Teacher } from './types';

interface ModuleFilterBarProps {
    searchTerm: string;
    onSearchChange: (value: string) => void;
    selectedProgram: string;
    onProgramChange: (value: string) => void;
    selectedTeacher: string;
    onTeacherChange: (value: string) => void;
    selectedStatus: string;
    onStatusChange: (value: string) => void;
    programs: Program[];
    teachers: Teacher[];
    onApply: () => void;
    onReset: () => void;
}

export function ModuleFilterBar({
    searchTerm,
    onSearchChange,
    selectedProgram,
    onProgramChange,
    selectedTeacher,
    onTeacherChange,
    selectedStatus,
    onStatusChange,
    programs,
    teachers,
    onApply,
    onReset,
}: ModuleFilterBarProps) {
    return (
        <div className="flex flex-col gap-4 rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <div className="flex flex-wrap items-center gap-3">
                <div className="relative min-w-[220px] flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                    <Input
                        placeholder="Search modules, codes, teachers..."
                        value={searchTerm}
                        onChange={(e) => onSearchChange(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && onApply()}
                        className="pl-9"
                    />
                </div>

                <div className="w-[200px]">
                    <select
                        value={selectedProgram}
                        onChange={(e) => onProgramChange(e.target.value)}
                        className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="">All Programs</option>
                        {programs.map((prog) => (
                            <option key={prog.id} value={prog.id}>
                                {prog.code} - {prog.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="w-[180px]">
                    <select
                        value={selectedTeacher}
                        onChange={(e) => onTeacherChange(e.target.value)}
                        className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="">All Instructors</option>
                        {teachers.map((teacher) => (
                            <option key={teacher.id} value={teacher.id}>
                                {teacher.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="w-[140px]">
                    <select
                        value={selectedStatus}
                        onChange={(e) => onStatusChange(e.target.value)}
                        className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="all">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <Button variant="default" size="sm" onClick={onApply}>
                    <Filter className="mr-1.5 size-4" />
                    Filter
                </Button>

                <Button variant="ghost" size="sm" onClick={onReset}>
                    <RotateCcw className="mr-1.5 size-4" />
                    Reset
                </Button>
            </div>
        </div>
    );
}
