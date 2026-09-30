import { Head, router, useForm } from '@inertiajs/react';
import {
    Building2,
    Check,
    CheckCircle2,
    Computer,
    DoorClosed,
    Edit2,
    Filter,
    Layers,
    MapPin,
    MonitorPlay,
    Plus,
    Power,
    Search,
    SlidersHorizontal,
    Users,
    Volume2,
    X,
} from 'lucide-react';
import React, { useState } from 'react';
import { toast } from 'sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

interface Campus {
    id: number;
    name: string;
    code: string;
    city?: string | null;
    address?: string | null;
    is_active: boolean;
    buildings?: Building[];
}

interface Building {
    id: number;
    campus_id: number;
    name: string;
    code?: string | null;
    is_active: boolean;
    campus?: Campus;
}

interface Room {
    id: number;
    building_id: number;
    name: string;
    code?: string | null;
    floor?: number | null;
    course_capacity: number;
    exam_capacity: number;
    has_projector: boolean;
    is_lab: boolean;
    has_computers: boolean;
    has_sound_system: boolean;
    is_active: boolean;
    building?: Building;
}

interface Stats {
    total_rooms: number;
    active_rooms: number;
    total_course_capacity: number;
    total_exam_capacity: number;
    total_campuses: number;
    total_buildings: number;
}

interface Filters {
    search: string;
    campus_id: string;
    building_id: string;
    is_active: string;
    has_projector: boolean;
    is_lab: boolean;
    has_computers: boolean;
    has_sound_system: boolean;
}

interface Props {
    rooms: Room[];
    campuses: Campus[];
    filters: Filters;
    stats: Stats;
}

export default function RoomsIndex({ rooms, campuses, filters, stats }: Props) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCampus, setSelectedCampus] = useState(filters.campus_id || '');
    const [selectedBuilding, setSelectedBuilding] = useState(filters.building_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.is_active || 'all');
    const [filterProjector, setFilterProjector] = useState(filters.has_projector || false);
    const [filterLab, setFilterLab] = useState(filters.is_lab || false);
    const [filterComputers, setFilterComputers] = useState(filters.has_computers || false);
    const [filterSound, setFilterSound] = useState(filters.has_sound_system || false);

    // Modals state
    const [isCreateRoomOpen, setIsCreateRoomOpen] = useState(false);
    const [editingRoom, setEditingRoom] = useState<Room | null>(null);
    const [isCreateCampusOpen, setIsCreateCampusOpen] = useState(false);
    const [isCreateBuildingOpen, setIsCreateBuildingOpen] = useState(false);

    // Forms
    const createRoomForm = useForm({
        building_id: campuses[0]?.buildings?.[0]?.id?.toString() || '',
        name: '',
        code: '',
        floor: '0',
        course_capacity: '40',
        exam_capacity: '20',
        has_projector: false,
        is_lab: false,
        has_computers: false,
        has_sound_system: false,
        is_active: true,
    });

    const editRoomForm = useForm({
        building_id: '',
        name: '',
        code: '',
        floor: '0',
        course_capacity: '40',
        exam_capacity: '20',
        has_projector: false,
        is_lab: false,
        has_computers: false,
        has_sound_system: false,
        is_active: true,
    });

    const campusForm = useForm({
        name: '',
        code: '',
        city: '',
        address: '',
    });

    const buildingForm = useForm({
        campus_id: campuses[0]?.id?.toString() || '',
        name: '',
        code: '',
    });

    const availableBuildingsForFilter = selectedCampus
        ? campuses.find((c) => c.id.toString() === selectedCampus)?.buildings || []
        : campuses.flatMap((c) => c.buildings || []);

    const handleApplyFilters = () => {
        router.get(
            '/rooms',
            {
                search: searchTerm || undefined,
                campus_id: selectedCampus || undefined,
                building_id: selectedBuilding || undefined,
                is_active: selectedStatus !== 'all' ? selectedStatus : undefined,
                has_projector: filterProjector ? '1' : undefined,
                is_lab: filterLab ? '1' : undefined,
                has_computers: filterComputers ? '1' : undefined,
                has_sound_system: filterSound ? '1' : undefined,
            },
            { preserveState: true }
        );
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedCampus('');
        setSelectedBuilding('');
        setSelectedStatus('all');
        setFilterProjector(false);
        setFilterLab(false);
        setFilterComputers(false);
        setFilterSound(false);

        router.get('/rooms', {}, { preserveState: true });
    };

    const handleOpenEdit = (room: Room) => {
        setEditingRoom(room);
        editRoomForm.setData({
            building_id: room.building_id.toString(),
            name: room.name,
            code: room.code || '',
            floor: room.floor?.toString() || '0',
            course_capacity: room.course_capacity.toString(),
            exam_capacity: room.exam_capacity.toString(),
            has_projector: room.has_projector,
            is_lab: room.is_lab,
            has_computers: room.has_computers,
            has_sound_system: room.has_sound_system,
            is_active: room.is_active,
        });
    };

    const submitCreateRoom = (e: React.FormEvent) => {
        e.preventDefault();
        const course = parseInt(createRoomForm.data.course_capacity, 10);
        const exam = parseInt(createRoomForm.data.exam_capacity, 10);

        if (exam > course) {
            toast.error('Validation Error: Exam capacity cannot exceed course capacity.');
            return;
        }

        createRoomForm.post('/rooms', {
            onSuccess: () => {
                setIsCreateRoomOpen(false);
                createRoomForm.reset();
                toast.success('Room created successfully.');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                if (firstError) toast.error(firstError as string);
            },
        });
    };

    const submitEditRoom = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingRoom) return;

        const course = parseInt(editRoomForm.data.course_capacity, 10);
        const exam = parseInt(editRoomForm.data.exam_capacity, 10);

        if (exam > course) {
            toast.error('Validation Error: Exam capacity cannot exceed course capacity.');
            return;
        }

        editRoomForm.put(`/rooms/${editingRoom.id}`, {
            onSuccess: () => {
                setEditingRoom(null);
                toast.success('Room updated successfully.');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                if (firstError) toast.error(firstError as string);
            },
        });
    };

    const toggleRoomActive = (room: Room) => {
        router.patch(
            `/rooms/${room.id}/toggle-active`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`Room ${room.name} ${room.is_active ? 'deactivated' : 'activated'}.`);
                },
            }
        );
    };

    const submitCreateCampus = (e: React.FormEvent) => {
        e.preventDefault();
        campusForm.post('/campuses', {
            onSuccess: () => {
                setIsCreateCampusOpen(false);
                campusForm.reset();
                toast.success('Campus created successfully.');
            },
            onError: (errors) => {
                const first = Object.values(errors)[0];
                if (first) toast.error(first as string);
            },
        });
    };

    const submitCreateBuilding = (e: React.FormEvent) => {
        e.preventDefault();
        buildingForm.post('/buildings', {
            onSuccess: () => {
                setIsCreateBuildingOpen(false);
                buildingForm.reset();
                toast.success('Building created successfully.');
            },
            onError: (errors) => {
                const first = Object.values(errors)[0];
                if (first) toast.error(first as string);
            },
        });
    };

    return (
        <>
            <Head title="Teaching Spaces & Referentials" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Teaching Spaces & Referentials
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Manage campuses, buildings, and rooms with explicit Course and Exam capacities.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" onClick={() => setIsCreateCampusOpen(true)}>
                            <MapPin className="mr-1.5 size-4" />
                            New Campus
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => setIsCreateBuildingOpen(true)}>
                            <Building2 className="mr-1.5 size-4" />
                            New Building
                        </Button>
                        <Button size="sm" onClick={() => setIsCreateRoomOpen(true)}>
                            <Plus className="mr-1.5 size-4" />
                            New Room
                        </Button>
                    </div>
                </div>

                {/* Metrics */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total Teaching Spaces</CardTitle>
                            <DoorClosed className="size-4 text-neutral-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_rooms}</div>
                            <p className="text-xs text-neutral-500">
                                {stats.active_rooms} active across {stats.total_campuses} campuses
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Course Capacity</CardTitle>
                            <Users className="size-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {stats.total_course_capacity}
                            </div>
                            <p className="text-xs text-neutral-500">Standard lecture seating threshold</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Exam Capacity</CardTitle>
                            <CheckCircle2 className="size-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {stats.total_exam_capacity}
                            </div>
                            <p className="text-xs text-neutral-500">Distanced seating density (anti-cheating)</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Buildings & Campuses</CardTitle>
                            <Building2 className="size-4 text-neutral-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_buildings}</div>
                            <p className="text-xs text-neutral-500">{stats.total_campuses} active campuses registered</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters & Search */}
                <Card>
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Filter className="size-4 text-neutral-500" />
                                <CardTitle className="text-base font-semibold">Filter Teaching Spaces</CardTitle>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button variant="ghost" size="sm" onClick={handleResetFilters}>
                                    Reset
                                </Button>
                                <Button size="sm" onClick={handleApplyFilters}>
                                    Apply Filters
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-medium">Search</Label>
                                <div className="relative">
                                    <Search className="absolute top-2.5 left-2.5 size-4 text-neutral-400" />
                                    <Input
                                        placeholder="Room, code, or building..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="pl-8"
                                        onKeyDown={(e) => e.key === 'Enter' && handleApplyFilters()}
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-medium">Campus</Label>
                                <select
                                    value={selectedCampus}
                                    onChange={(e) => {
                                        setSelectedCampus(e.target.value);
                                        setSelectedBuilding('');
                                    }}
                                    className="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="">All Campuses</option>
                                    {campuses.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name} ({c.code})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-medium">Building</Label>
                                <select
                                    value={selectedBuilding}
                                    onChange={(e) => setSelectedBuilding(e.target.value)}
                                    className="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="">All Buildings</option>
                                    {availableBuildingsForFilter.map((b) => (
                                        <option key={b.id} value={b.id}>
                                            {b.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-medium">Status</Label>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => setSelectedStatus(e.target.value)}
                                    className="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="all">All Statuses</option>
                                    <option value="1">Active Only</option>
                                    <option value="0">Inactive Only</option>
                                </select>
                            </div>
                        </div>

                        {/* Equipment Toggles */}
                        <div className="flex flex-wrap items-center gap-4 pt-2">
                            <span className="text-xs font-semibold text-neutral-500 uppercase">Equipment:</span>
                            <label className="flex cursor-pointer items-center gap-1.5 text-xs">
                                <Checkbox
                                    checked={filterProjector}
                                    onCheckedChange={(c) => setFilterProjector(!!c)}
                                />
                                Projector
                            </label>
                            <label className="flex cursor-pointer items-center gap-1.5 text-xs">
                                <Checkbox
                                    checked={filterLab}
                                    onCheckedChange={(c) => setFilterLab(!!c)}
                                />
                                Computer Lab
                            </label>
                            <label className="flex cursor-pointer items-center gap-1.5 text-xs">
                                <Checkbox
                                    checked={filterComputers}
                                    onCheckedChange={(c) => setFilterComputers(!!c)}
                                />
                                Student PCs
                            </label>
                            <label className="flex cursor-pointer items-center gap-1.5 text-xs">
                                <Checkbox
                                    checked={filterSound}
                                    onCheckedChange={(c) => setFilterSound(!!c)}
                                />
                                Sound System
                            </label>
                        </div>
                    </CardContent>
                </Card>

                {/* Rooms Grid / Table */}
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
                            <Button variant="outline" size="sm" onClick={handleResetFilters} className="mt-4">
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
                                    {rooms.map((room) => {
                                        const ratio =
                                            room.course_capacity > 0
                                                ? Math.round((room.exam_capacity / room.course_capacity) * 100)
                                                : 0;

                                        return (
                                            <tr
                                                key={room.id}
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
                                                        <Badge variant="outline" className="px-2.5 py-1 text-sm font-semibold border-emerald-500/30 text-emerald-700 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/20">
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
                                                            onClick={() => handleOpenEdit(room)}
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
                                                            onClick={() => toggleRoomActive(room)}
                                                            title={room.is_active ? 'Deactivate' : 'Activate'}
                                                        >
                                                            <Power className="size-3.5" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* CREATE ROOM DIALOG */}
            <Dialog open={isCreateRoomOpen} onOpenChange={setIsCreateRoomOpen}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={submitCreateRoom}>
                        <DialogHeader>
                            <DialogTitle>Add New Room</DialogTitle>
                            <DialogDescription>
                                Register a teaching space with both lecture and distanced exam capacities.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 py-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="building_id">Building *</Label>
                                <select
                                    id="building_id"
                                    value={createRoomForm.data.building_id}
                                    onChange={(e) => createRoomForm.setData('building_id', e.target.value)}
                                    className="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                                    required
                                >
                                    {campuses.map((c) => (
                                        <optgroup key={c.id} label={`${c.name} (${c.code})`}>
                                            {c.buildings?.map((b) => (
                                                <option key={b.id} value={b.id}>
                                                    {b.name}
                                                </option>
                                            ))}
                                        </optgroup>
                                    ))}
                                </select>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="name">Room Name *</Label>
                                    <Input
                                        id="name"
                                        placeholder="e.g. Salle 101, Amphi 1"
                                        value={createRoomForm.data.name}
                                        onChange={(e) => createRoomForm.setData('name', e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="code">Room Code</Label>
                                    <Input
                                        id="code"
                                        placeholder="e.g. A-101"
                                        value={createRoomForm.data.code}
                                        onChange={(e) => createRoomForm.setData('code', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="floor">Floor</Label>
                                    <Input
                                        id="floor"
                                        type="number"
                                        value={createRoomForm.data.floor}
                                        onChange={(e) => createRoomForm.setData('floor', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="course_capacity">Course Cap. *</Label>
                                    <Input
                                        id="course_capacity"
                                        type="number"
                                        min="1"
                                        value={createRoomForm.data.course_capacity}
                                        onChange={(e) => {
                                            const course = e.target.value;
                                            const examVal = Math.floor(parseInt(course || '0', 10) / 2);
                                            createRoomForm.setData({
                                                ...createRoomForm.data,
                                                course_capacity: course,
                                                exam_capacity: examVal > 0 ? examVal.toString() : '1',
                                            });
                                        }}
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="exam_capacity">Exam Cap. *</Label>
                                    <Input
                                        id="exam_capacity"
                                        type="number"
                                        min="1"
                                        max={createRoomForm.data.course_capacity}
                                        value={createRoomForm.data.exam_capacity}
                                        onChange={(e) => createRoomForm.setData('exam_capacity', e.target.value)}
                                        required
                                    />
                                </div>
                            </div>

                            {/* Dual capacity indicator */}
                            <div className="rounded-lg bg-neutral-50 p-2.5 text-xs text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                                💡 <strong>Exam Capacity Rule:</strong> Strictly cannot exceed Course Capacity. Default is 50% for exam distancing.
                            </div>

                            {/* Equipment toggles */}
                            <div className="space-y-2 pt-2">
                                <Label className="text-xs font-semibold text-neutral-500 uppercase">Equipment & Facilities</Label>
                                <div className="grid grid-cols-2 gap-2">
                                    <label className="flex cursor-pointer items-center gap-2 text-xs">
                                        <Checkbox
                                            checked={createRoomForm.data.has_projector}
                                            onCheckedChange={(c) => createRoomForm.setData('has_projector', !!c)}
                                        />
                                        Video Projector
                                    </label>
                                    <label className="flex cursor-pointer items-center gap-2 text-xs">
                                        <Checkbox
                                            checked={createRoomForm.data.is_lab}
                                            onCheckedChange={(c) => createRoomForm.setData('is_lab', !!c)}
                                        />
                                        Computer Lab
                                    </label>
                                    <label className="flex cursor-pointer items-center gap-2 text-xs">
                                        <Checkbox
                                            checked={createRoomForm.data.has_computers}
                                            onCheckedChange={(c) => createRoomForm.setData('has_computers', !!c)}
                                        />
                                        Student PCs
                                    </label>
                                    <label className="flex cursor-pointer items-center gap-2 text-xs">
                                        <Checkbox
                                            checked={createRoomForm.data.has_sound_system}
                                            onCheckedChange={(c) => createRoomForm.setData('has_sound_system', !!c)}
                                        />
                                        Sound System
                                    </label>
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setIsCreateRoomOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={createRoomForm.processing}>
                                {createRoomForm.processing ? 'Saving...' : 'Create Room'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* EDIT ROOM DIALOG */}
            <Dialog open={!!editingRoom} onOpenChange={(open) => !open && setEditingRoom(null)}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={submitEditRoom}>
                        <DialogHeader>
                            <DialogTitle>Edit Room: {editingRoom?.name}</DialogTitle>
                            <DialogDescription>
                                Update physical teaching space attributes and seating thresholds.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 py-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_building_id">Building *</Label>
                                <select
                                    id="edit_building_id"
                                    value={editRoomForm.data.building_id}
                                    onChange={(e) => editRoomForm.setData('building_id', e.target.value)}
                                    className="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                                    required
                                >
                                    {campuses.map((c) => (
                                        <optgroup key={c.id} label={`${c.name} (${c.code})`}>
                                            {c.buildings?.map((b) => (
                                                <option key={b.id} value={b.id}>
                                                    {b.name}
                                                </option>
                                            ))}
                                        </optgroup>
                                    ))}
                                </select>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="edit_name">Room Name *</Label>
                                    <Input
                                        id="edit_name"
                                        value={editRoomForm.data.name}
                                        onChange={(e) => editRoomForm.setData('name', e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="edit_code">Room Code</Label>
                                    <Input
                                        id="edit_code"
                                        value={editRoomForm.data.code}
                                        onChange={(e) => editRoomForm.setData('code', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="edit_floor">Floor</Label>
                                    <Input
                                        id="edit_floor"
                                        type="number"
                                        value={editRoomForm.data.floor}
                                        onChange={(e) => editRoomForm.setData('floor', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="edit_course_capacity">Course Cap. *</Label>
                                    <Input
                                        id="edit_course_capacity"
                                        type="number"
                                        min="1"
                                        value={editRoomForm.data.course_capacity}
                                        onChange={(e) => editRoomForm.setData('course_capacity', e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="edit_exam_capacity">Exam Cap. *</Label>
                                    <Input
                                        id="edit_exam_capacity"
                                        type="number"
                                        min="1"
                                        max={editRoomForm.data.course_capacity}
                                        value={editRoomForm.data.exam_capacity}
                                        onChange={(e) => editRoomForm.setData('exam_capacity', e.target.value)}
                                        required
                                    />
                                </div>
                            </div>

                            {/* Equipment toggles */}
                            <div className="space-y-2 pt-2">
                                <Label className="text-xs font-semibold text-neutral-500 uppercase">Equipment & Facilities</Label>
                                <div className="grid grid-cols-2 gap-2">
                                    <label className="flex cursor-pointer items-center gap-2 text-xs">
                                        <Checkbox
                                            checked={editRoomForm.data.has_projector}
                                            onCheckedChange={(c) => editRoomForm.setData('has_projector', !!c)}
                                        />
                                        Video Projector
                                    </label>
                                    <label className="flex cursor-pointer items-center gap-2 text-xs">
                                        <Checkbox
                                            checked={editRoomForm.data.is_lab}
                                            onCheckedChange={(c) => editRoomForm.setData('is_lab', !!c)}
                                        />
                                        Computer Lab
                                    </label>
                                    <label className="flex cursor-pointer items-center gap-2 text-xs">
                                        <Checkbox
                                            checked={editRoomForm.data.has_computers}
                                            onCheckedChange={(c) => editRoomForm.setData('has_computers', !!c)}
                                        />
                                        Student PCs
                                    </label>
                                    <label className="flex cursor-pointer items-center gap-2 text-xs">
                                        <Checkbox
                                            checked={editRoomForm.data.has_sound_system}
                                            onCheckedChange={(c) => editRoomForm.setData('has_sound_system', !!c)}
                                        />
                                        Sound System
                                    </label>
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setEditingRoom(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editRoomForm.processing}>
                                {editRoomForm.processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* CREATE CAMPUS DIALOG */}
            <Dialog open={isCreateCampusOpen} onOpenChange={setIsCreateCampusOpen}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={submitCreateCampus}>
                        <DialogHeader>
                            <DialogTitle>Add Campus</DialogTitle>
                            <DialogDescription>
                                Register a new institutional campus (e.g. Casablanca, Rabat).
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-3 py-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="campus_name">Campus Name *</Label>
                                <Input
                                    id="campus_name"
                                    placeholder="e.g. Campus Casablanca"
                                    value={campusForm.data.name}
                                    onChange={(e) => campusForm.setData('name', e.target.value)}
                                    required
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="campus_code">Code (Unique) *</Label>
                                    <Input
                                        id="campus_code"
                                        placeholder="e.g. CASA"
                                        value={campusForm.data.code}
                                        onChange={(e) => campusForm.setData('code', e.target.value.toUpperCase())}
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="campus_city">City</Label>
                                    <Input
                                        id="campus_city"
                                        placeholder="e.g. Casablanca"
                                        value={campusForm.data.city}
                                        onChange={(e) => campusForm.setData('city', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="campus_address">Address</Label>
                                <Input
                                    id="campus_address"
                                    placeholder="e.g. Boulevard Bir Anzarane"
                                    value={campusForm.data.address}
                                    onChange={(e) => campusForm.setData('address', e.target.value)}
                                />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setIsCreateCampusOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={campusForm.processing}>
                                {campusForm.processing ? 'Saving...' : 'Create Campus'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* CREATE BUILDING DIALOG */}
            <Dialog open={isCreateBuildingOpen} onOpenChange={setIsCreateBuildingOpen}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={submitCreateBuilding}>
                        <DialogHeader>
                            <DialogTitle>Add Building</DialogTitle>
                            <DialogDescription>
                                Register a building attached to a campus.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-3 py-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="building_campus_id">Campus *</Label>
                                <select
                                    id="building_campus_id"
                                    value={buildingForm.data.campus_id}
                                    onChange={(e) => buildingForm.setData('campus_id', e.target.value)}
                                    className="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                                    required
                                >
                                    {campuses.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name} ({c.code})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="building_name">Building Name *</Label>
                                    <Input
                                        id="building_name"
                                        placeholder="e.g. Bâtiment A"
                                        value={buildingForm.data.name}
                                        onChange={(e) => buildingForm.setData('name', e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="building_code">Code</Label>
                                    <Input
                                        id="building_code"
                                        placeholder="e.g. BAT-A"
                                        value={buildingForm.data.code}
                                        onChange={(e) => buildingForm.setData('code', e.target.value.toUpperCase())}
                                    />
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setIsCreateBuildingOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={buildingForm.processing}>
                                {buildingForm.processing ? 'Saving...' : 'Create Building'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

RoomsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'Teaching Spaces',
            href: '/rooms',
        },
    ],
};
