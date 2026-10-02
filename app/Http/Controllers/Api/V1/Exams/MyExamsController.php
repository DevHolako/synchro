<?php

namespace App\Http\Controllers\Api\V1\Exams;

use App\Actions\Exams\ListMyExamsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\MyExamResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyExamsController extends Controller
{
    public function __invoke(Request $request, ListMyExamsAction $listMyExams): AnonymousResourceCollection
    {
        $exams = $listMyExams->execute($request->user());

        return MyExamResource::collection($exams);
    }
}
