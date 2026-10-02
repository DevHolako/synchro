<?php

namespace App\Http\Controllers\Api\V1\Exams;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\ModuleResource;
use App\Models\Exam;
use App\Models\ExamRoomAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MyExamsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null || ! $user->hasPermission(Permission::ViewExams)) {
            throw new AuthorizationException;
        }

        $exams = Exam::query()
            ->concerning($user)
            ->with([
                'module:id,code,name,color_code,program_id',
                'examPeriod:id,name,session_type',
                'roomAssignments.room:id,name,code,building_id',
                'roomAssignments.room.building:id,name',
                'candidates' => fn ($candidates) => $candidates->where('student_id', $user->id)->with('roomAssignment.room:id,name,code'),
            ])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $data = $exams->map(function (Exam $exam): array {
            $candidate = $exam->candidates->first();
            $mySeat = null;

            if ($candidate !== null) {
                $convocationStatus = null;
                if ($exam->state->isVisibleToCandidates()) {
                    $convocationStatus = Storage::disk('local')->exists($candidate->convocationPath()) ? 'ready' : 'pending';
                }

                $mySeat = [
                    'candidate_id' => $candidate->id,
                    'room' => $candidate->roomAssignment->room->name,
                    'room_code' => $candidate->roomAssignment->room->code,
                    'seat_number' => $candidate->seat_number,
                    'convocation_uuid' => $candidate->convocation_uuid,
                    'convocation_status' => $convocationStatus,
                    'checked_in_at' => $candidate->checked_in_at?->toIso8601String(),
                ];
            }

            return [
                'id' => $exam->id,
                'starts_at' => $exam->starts_at->toIso8601String(),
                'ends_at' => $exam->ends_at->toIso8601String(),
                'state' => $exam->state->value,
                'period' => [
                    'id' => $exam->examPeriod->id,
                    'name' => $exam->examPeriod->name,
                    'session_type' => $exam->examPeriod->session_type->value,
                ],
                'module' => new ModuleResource($exam->module),
                'my_seat' => $mySeat,
                'assigned_rooms' => $exam->roomAssignments->map(fn (ExamRoomAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'name' => $assignment->room->name,
                    'code' => $assignment->room->code,
                    'building' => $assignment->room->building?->name,
                    'first_surname' => $assignment->first_surname,
                    'last_surname' => $assignment->last_surname,
                    'allocated_students_count' => $assignment->allocated_students_count,
                ])->values()->all(),
            ];
        });

        return response()->json(['data' => $data]);
    }
}
