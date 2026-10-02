<?php

namespace App\Http\Controllers\Api\V1\CheckIn;

use App\Actions\Exams\CheckInCandidateAction;
use App\Actions\Exams\VerifyConvocationAction;
use App\Http\Controllers\Controller;
use App\Models\ExamCandidate;
use App\Models\SupersededConvocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ScanCheckInController extends Controller
{
    public function __invoke(
        Request $request,
        VerifyConvocationAction $verifyConvocation,
        CheckInCandidateAction $checkInAction,
    ): JsonResponse {
        $raw = (string) ($request->input('uuid') ?? $request->input('token') ?? $request->input('qr_code') ?? '');

        if ($raw === '') {
            return response()->json([
                'type' => 'https://synchro.isga.ma/problems/validation-error',
                'title' => 'Validation Failed',
                'status' => 422,
                'detail' => 'The QR code token or uuid is required.',
                'instance' => $request->getRequestUri(),
                'errors' => ['uuid' => ['The QR code token or uuid is required.']],
            ], 422, ['Content-Type' => 'application/problem+json']);
        }

        // Extract UUID if a full URL was scanned
        $uuid = $raw;
        if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $raw, $matches)) {
            $uuid = $matches[0];
        }

        $convocation = $verifyConvocation->execute($uuid);

        if ($convocation instanceof SupersededConvocation) {
            $currentCandidate = ExamCandidate::query()
                ->where('exam_id', $convocation->exam_id)
                ->where('student_id', $convocation->student_id)
                ->with('roomAssignment.room')
                ->first();

            return response()->json([
                'status' => 'superseded',
                'message' => __('messages.exam_superseded_notice'),
                'superseded_at' => $convocation->created_at->toIso8601String(),
                'candidate' => [
                    'id' => $convocation->id,
                    'name' => $convocation->student->officialName(),
                    'matricule' => $convocation->student->studentProfile?->matricule,
                    'current_room' => $currentCandidate?->roomAssignment?->room?->name,
                    'current_seat_number' => $currentCandidate?->seat_number,
                ],
            ], 409);
        }

        /** @var ExamCandidate $convocation */
        Gate::authorize('checkIn', $convocation->exam);

        try {
            $checkedIn = $checkInAction->execute($convocation, $request->user());

            return response()->json([
                'status' => 'success',
                'message' => __('messages.exam_checked_in', ['name' => $checkedIn->student->officialName()]),
                'candidate' => [
                    'id' => $checkedIn->id,
                    'name' => $checkedIn->student->officialName(),
                    'matricule' => $checkedIn->student->studentProfile?->matricule,
                    'room' => $checkedIn->roomAssignment->room->name,
                    'seat_number' => $checkedIn->seat_number,
                    'checked_in_at' => $checkedIn->checked_in_at?->toIso8601String(),
                ],
            ]);
        } catch (ValidationException $e) {
            $errorMessage = $e->errors()['check_in'][0] ?? $e->getMessage();

            $candidatePayload = [
                'id' => $convocation->id,
                'name' => $convocation->student->officialName(),
                'matricule' => $convocation->student->studentProfile?->matricule,
                'room' => $convocation->roomAssignment->room->name,
                'seat_number' => $convocation->seat_number,
            ];

            if ($convocation->checked_in_at !== null) {
                return response()->json([
                    'status' => 'already_checked_in',
                    'message' => $errorMessage,
                    'checked_in_at' => $convocation->checked_in_at->toIso8601String(),
                    'candidate' => $candidatePayload,
                ], 422);
            }

            $userRoomId = $convocation->exam->invigilatedAssignmentId($request->user());
            if ($userRoomId !== null && $userRoomId !== $convocation->exam_room_assignment_id) {
                return response()->json([
                    'status' => 'wrong_room',
                    'message' => $errorMessage,
                    'assigned_room' => $convocation->roomAssignment->room->name,
                    'candidate' => $candidatePayload,
                ], 422);
            }

            return response()->json([
                'status' => 'closed',
                'message' => $errorMessage,
                'candidate' => $candidatePayload,
            ], 422);
        }
    }
}
