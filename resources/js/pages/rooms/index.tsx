import { Head, router } from '@inertiajs/react';
import { Building2, MapPin, Plus } from 'lucide-react';
import React, { useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { BuildingDialog } from './components/building-dialog';
import { CampusDialog } from './components/campus-dialog';
import { RoomDialog } from './components/room-dialog';
import { RoomsFilterBar } from './components/rooms-filter-bar';
import { RoomsStats } from './components/rooms-stats';
import { RoomsTable } from './components/rooms-table';
import type { Campus, Filters, Room, Stats } from './components/types';

interface RoomsIndexProps {
    rooms: Room[];
    campuses: Campus[];
    filters: Filters;
    stats: Stats;
}

export default function RoomsIndex({
    rooms,
    campuses,
    filters,
    stats,
}: RoomsIndexProps) {
    // Filter local states
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCampus, setSelectedCampus] = useState(
        filters.campus_id || '',
    );
    const [selectedBuilding, setSelectedBuilding] = useState(
        filters.building_id || '',
    );
    const [selectedStatus, setSelectedStatus] = useState(
        filters.is_active || 'all',
    );
    const [filterProjector, setFilterProjector] = useState(
        filters.has_projector || false,
    );
    const [filterLab, setFilterLab] = useState(filters.is_lab || false);
    const [filterComputers, setFilterComputers] = useState(
        filters.has_computers || false,
    );
    const [filterSound, setFilterSound] = useState(
        filters.has_sound_system || false,
    );

    // Modal dialogs state
    const [isCreateRoomOpen, setIsCreateRoomOpen] = useState(false);
    const [editingRoom, setEditingRoom] = useState<Room | null>(null);
    const [isCreateCampusOpen, setIsCreateCampusOpen] = useState(false);
    const [isCreateBuildingOpen, setIsCreateBuildingOpen] = useState(false);

    const handleApplyFilters = () => {
        router.get(
            '/rooms',
            {
                search: searchTerm || undefined,
                campus_id: selectedCampus || undefined,
                building_id: selectedBuilding || undefined,
                is_active:
                    selectedStatus !== 'all' ? selectedStatus : undefined,
                has_projector: filterProjector ? '1' : undefined,
                is_lab: filterLab ? '1' : undefined,
                has_computers: filterComputers ? '1' : undefined,
                has_sound_system: filterSound ? '1' : undefined,
            },
            { preserveState: true },
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

    const handleToggleActive = (room: Room) => {
        router.patch(
            `/rooms/${room.id}/toggle-active`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(
                        `Room ${room.name} ${room.is_active ? 'deactivated' : 'activated'}.`,
                    );
                },
            },
        );
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
                            Manage campuses, buildings, and rooms with explicit
                            Course and Exam capacities.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setIsCreateCampusOpen(true)}
                        >
                            <MapPin className="mr-1.5 size-4" />
                            New Campus
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setIsCreateBuildingOpen(true)}
                        >
                            <Building2 className="mr-1.5 size-4" />
                            New Building
                        </Button>
                        <Button
                            size="sm"
                            onClick={() => setIsCreateRoomOpen(true)}
                        >
                            <Plus className="mr-1.5 size-4" />
                            New Room
                        </Button>
                    </div>
                </div>

                {/* Metrics */}
                <RoomsStats stats={stats} />

                {/* Filters */}
                <RoomsFilterBar
                    campuses={campuses}
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    selectedCampus={selectedCampus}
                    onCampusChange={(c) => {
                        setSelectedCampus(c);
                        setSelectedBuilding('');
                    }}
                    selectedBuilding={selectedBuilding}
                    onBuildingChange={setSelectedBuilding}
                    selectedStatus={selectedStatus}
                    onStatusChange={setSelectedStatus}
                    filterProjector={filterProjector}
                    onProjectorChange={setFilterProjector}
                    filterLab={filterLab}
                    onLabChange={setFilterLab}
                    filterComputers={filterComputers}
                    onComputersChange={setFilterComputers}
                    filterSound={filterSound}
                    onSoundChange={setFilterSound}
                    onApplyFilters={handleApplyFilters}
                    onResetFilters={handleResetFilters}
                />

                {/* Table */}
                <RoomsTable
                    rooms={rooms}
                    onEdit={(room) => setEditingRoom(room)}
                    onToggleActive={handleToggleActive}
                    onResetFilters={handleResetFilters}
                />
            </div>

            {/* Dialogs */}
            <RoomDialog
                open={isCreateRoomOpen}
                onOpenChange={setIsCreateRoomOpen}
                campuses={campuses}
            />

            <RoomDialog
                open={Boolean(editingRoom)}
                onOpenChange={(open) => !open && setEditingRoom(null)}
                campuses={campuses}
                roomToEdit={editingRoom}
            />

            <CampusDialog
                open={isCreateCampusOpen}
                onOpenChange={setIsCreateCampusOpen}
            />

            <BuildingDialog
                open={isCreateBuildingOpen}
                onOpenChange={setIsCreateBuildingOpen}
                campuses={campuses}
            />
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
