import {
    CheckCircle2,
    Computer,
    DoorClosed,
    Edit2,
    MonitorPlay,
    Power,
    SlidersHorizontal,
    Users,
    Volume2,
} from 'lucide-react';
import React, { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Room } from './types';

interface RoomsTableProps {
    rooms: Room[];
    onEdit: (room: Room) => void;
    onToggleActive: (room: Room) => void;
    onResetFilters: () => void;
}

const RoomRow = memo(function RoomRow({
    room,
    onEdit,
    onToggleActive,
}: {
    room: Room;
    onEdit: (room: Room) => void;
    onToggleActive: (room: Room) => void;
}) {
    const ratio =
        room.course_capacity > 0
            ? Math.round((room.exam_capacity / room.course_capacity) * 100)
            : 0;

    return (
        <tr
            className={`transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40 ${
                !room.is_active ? 'opacity-60 bg-neutral-50/20' : ''
            }`}
        >
            <td className="px-6 py-4">
                <div className="font-semibold text-neutral-900 dark:text-neutral-100">
                    {room.name}
                </div>
                {room.code && (
                    <div className="text-xs text-neutral-500 font-mono">
                        {room.code}
                    </div>
                )}
                {room.floor !== null && (
                    <div className="text-xs text-neutral-400">
                        Floor {room.floor}
                    </div>
                )}
            </td>

            <td className="px-6 py-4">
                <div className="font-medium text-neutral-800 dark:text-neutral-200">
                    {room.building?.name || 'Unknown Building'}
                </div>
                <div className="text-xs text-neutral-500">
                    {room.building?.campus?.name} ({room.building?.campus?.code})
                </div>
            </td>

            <td className="px-6 py-4 text-center">
                <Badge variant="secondary" className="px-2.5 py-1 text-sm font-semibold">
                    <Users className="mr-1 size-3.5 text-blue-500" />
                    {room.course_capacity} seats
                </Badge>
            </td>

            <td className="px-6 py-4 text-center">
                <div className="inline-flex flex-col items-center">
                    <Badge
                        variant="outline"
                        className="px-2.5 py-1 text-sm font-semibold border-emerald-500/30 text-emerald-700 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/20"
                    >
                        <CheckCircle2 className="mr-1 size-3.5 text-emerald-500" />
                        {room.exam_capacity} seats
                    </Badge>
                    <span className="text-[10px] text-neutral-400 mt-0.5">
                        {ratio}% density
                    </span>
                </div>
            </td>

            <td className="px-6 py-4">
                <div className="flex flex-wrap gap-1.5">
                    {room.has_projector && (
                        <Badge variant="outline" className="text-[11px] gap-1 bg-neutral-50 dark:bg-neutral-800">
                            <MonitorPlay className="size-3 text-indigo-500" />
                            Projector
                        </Badge>
                    )}
                    {room.is_lab && (
                        <Badge variant="outline" className="text-[11px] gap-1 bg-neutral-50 dark:bg-neutral-800">
                            <SlidersHorizontal className="size-3 text-purple-500" />
                            Lab
                        </Badge>
                    )}
                    {room.has_computers && (
                        <Badge variant="outline" className="text-[11px] gap-1 bg-neutral-50 dark:bg-neutral-800">
                            <Computer className="size-3 text-cyan-500" />
                            PCs
                        </Badge>
                    )}
                    {room.has_sound_system && (
                        <Badge variant="outline" className="text-[11px] gap-1 bg-neutral-50 dark:bg-neutral-800">
                            <Volume2 className="size-3 text-amber-500" />
                            Audio
                        </Badge>
                    )}
                    {!room.has_projector &&
                        !room.is_lab &&
                        !room.has_computers &&
                        !room.has_sound_system && (
                            <span className="text-xs text-neutral-400">—</span>
                        )}
                </div>
            </td>

            <td className="px-6 py-4">
                {room.is_active ? (
                    <Badge className="bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-transparent">
                        Active
                    </Badge>
                ) : (
                    <Badge variant="secondary" className="text-neutral-500">
                        Inactive
                    </Badge>
                )}
            </td>

            <td className="px-6 py-4 text-right">
                <div className="flex items-center justify-end gap-1.5">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        onClick={() => onEdit(room)}
                        title="Edit Room"
                    >
                        <Edit2 className="size-3.5" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        className={`size-8 ${
                            room.is_active
                                ? 'text-amber-600 hover:text-amber-700 dark:text-amber-400'
                                : 'text-emerald-600 hover:text-emerald-700'
                        }`}
                        onClick={() => onToggleActive(room)}
                        title={room.is_active ? 'Deactivate' : 'Activate'}
                    >
                        <Power className="size-3.5" />
                    </Button>
                </div>
            </td>
        </tr>
    );
});

export function RoomsTable({ rooms, onEdit, onToggleActive, onResetFilters }: RoomsTableProps) {
    return (
        <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <div className="border-b border-neutral-200 px-6 py-4 dark:border-neutral-800">
                <div className="flex items-center justify-between">
                    <h2 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                        Registered Rooms ({rooms.length})
                    </h2>
                    <span className="text-xs text-neutral-500">
                        Showing all spaces matching active filters
                    </span>
                </div>
            </div>

            {rooms.length === 0 ? (
                <div className="flex flex-col items-center justify-center p-12 text-center">
                    <DoorClosed className="size-12 text-neutral-300 dark:text-neutral-700" />
                    <h3 className="mt-4 text-base font-medium text-neutral-900 dark:text-neutral-100">
                        No rooms match your filters
                    </h3>
                    <p className="mt-1 text-sm text-neutral-500">
                        Try adjusting your search query or clear the active filters.
                    </p>
                    <Button variant="outline" size="sm" onClick={onResetFilters} className="mt-4">
                        Reset Filters
                    </Button>
                </div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-neutral-200 bg-neutral-50 text-xs font-semibold text-neutral-600 uppercase dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                            <tr>
                                <th className="px-6 py-3.5">Room & Code</th>
                                <th className="px-6 py-3.5">Location</th>
                                <th className="px-6 py-3.5 text-center">Course Capacity</th>
                                <th className="px-6 py-3.5 text-center">Exam Capacity</th>
                                <th className="px-6 py-3.5">Equipment</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                            {rooms.map((room) => (
                                <RoomRow
                                    key={room.id}
                                    room={room}
                                    onEdit={onEdit}
                                    onToggleActive={onToggleActive}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
