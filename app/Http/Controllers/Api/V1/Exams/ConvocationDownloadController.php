<?php

namespace App\Http\Controllers\Api\V1\Exams;

use App\Actions\Exams\FindCandidateConvocationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConvocationDownloadController extends Controller
{
    public function __invoke(Request $request, int $id, FindCandidateConvocationAction $findConvocation): StreamedResponse|JsonResponse
    {
        $stream = $findConvocation->execute($id, $request->user());

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
