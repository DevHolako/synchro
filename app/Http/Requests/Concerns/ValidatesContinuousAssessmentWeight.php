<?php

namespace App\Http\Requests\Concerns;

use App\Models\Module;

/**
 * The module form requests' shared rule for the grade weighting: a whole percent from 0 to
 * `Module::MAX_CONTINUOUS_ASSESSMENT_WEIGHT`, with one translated message when out of range.
 */
trait ValidatesContinuousAssessmentWeight
{
    /**
     * @return list<string>
     */
    protected function continuousAssessmentWeightRules(): array
    {
        return ['integer', 'min:0', 'max:'.Module::MAX_CONTINUOUS_ASSESSMENT_WEIGHT];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $outOfRange = Module::continuousAssessmentWeightError();

        return [
            'continuous_assessment_weight.min' => $outOfRange,
            'continuous_assessment_weight.max' => $outOfRange,
        ];
    }
}
