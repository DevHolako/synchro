<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\CreateExamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\StoreExamRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ExamStoreController extends Controller
{
    public function __invoke(StoreExamRequest $request, CreateExamAction $action): RedirectResponse
    {
        $action->execute($request->payload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_created')]);

        return back();
    }
}
