<?php

namespace App\Http\Controllers\Api\V1\Exams;

use App\Actions\Exams\DownloadExamDocumentAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamCandidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConvocationDownloadController extends Controller
{
    public function __invoke(Request $request, int $id, DownloadExamDocumentAction $download): StreamedResponse|JsonResponse
    {
        $user = $request->user();

        // Check if $id refers to an Exam or an ExamCandidate
        $candidate = ExamCandidate::where('id', $id)
            ->orWhere(fn ($q) => $q->where('exam_id', $id)->where('student_id', $user?->id))
            ->with(['exam.module', 'roomAssignment.room'])
            ->first();

        if ($candidate === null) {
            $exam = Exam::find($id);
            if ($exam !== null) {
                Gate::authorize('downloadConvocation', $exam);
            }

            return response()->json([
                'type' => 'https://synchro.isga.ma/problems/not-found',
                'title' => 'Resource Not Found',
                'status' => 404,
                'detail' => 'No convocation found for this exam and student.',
                'instance' => $request->getRequestUri(),
            ], 404, ['Content-Type' => 'application/problem+json']);
        }

        Gate::authorize('downloadConvocation', $candidate->exam);

        $stream = $download->execute(
            $candidate->convocationPath(),
            __('documents.convocation_filename', ['code' => $candidate->exam->module->code], 'fr'),
        );

        if ($stream === null) {
            return response()->json([
                'type' => 'https://synchro.isga.ma/problems/conflict',
                'title' => 'Document Pending',
                'status' => 409,
                'detail' => __('messages.exam_document_pending'),
                'instance' => $request->getRequestUri(),
            ], 409, ['Content-Type' => 'application/problem+json']);
        }

        return $stream;
    }
}
