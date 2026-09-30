<?php

namespace App\Http\Controllers\Web\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = Room::query()->with(['building.campus']);

        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('building', function (Builder $b) use ($search) {
                        $b->where('name', 'like', "%{$search}%")
                            ->orWhereHas('campus', fn (Builder $c) => $c->where('name', 'like', "%{$search}%"));
                    });
            });
        }

        if ($campusId = $request->integer('campus_id')) {
            $query->whereHas('building', fn (Builder $b) => $b->where('campus_id', $campusId));
        }

        if ($buildingId = $request->integer('building_id')) {
            $query->where('building_id', $buildingId);
        }

        if ($request->has('is_active') && $request->input('is_active') !== 'all') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->boolean('has_projector')) {
            $query->where('has_projector', true);
        }

        if ($request->boolean('is_lab')) {
            $query->where('is_lab', true);
        }

        if ($request->boolean('has_computers')) {
            $query->where('has_computers', true);
        }

        if ($request->boolean('has_sound_system')) {
            $query->where('has_sound_system', true);
        }

        $rooms = $query->orderBy('name')->get();

        $campuses = Campus::with(['buildings' => fn ($b) => $b->orderBy('name')])
            ->orderBy('name')
            ->get();

        $stats = [
            'total_rooms' => Room::count(),
            'active_rooms' => Room::where('is_active', true)->count(),
            'total_course_capacity' => Room::where('is_active', true)->sum('course_capacity'),
            'total_exam_capacity' => Room::where('is_active', true)->sum('exam_capacity'),
            'total_campuses' => Campus::where('is_active', true)->count(),
            'total_buildings' => Building::where('is_active', true)->count(),
        ];

        return Inertia::render('rooms/index', [
            'rooms' => $rooms,
            'campuses' => $campuses,
            'filters' => [
                'search' => $request->input('search', ''),
                'campus_id' => $request->input('campus_id', ''),
                'building_id' => $request->input('building_id', ''),
                'is_active' => $request->input('is_active', 'all'),
                'has_projector' => $request->boolean('has_projector'),
                'is_lab' => $request->boolean('is_lab'),
                'has_computers' => $request->boolean('has_computers'),
                'has_sound_system' => $request->boolean('has_sound_system'),
            ],
            'stats' => $stats,
        ]);
    }
}
