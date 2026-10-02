<?php

namespace App\Http\Resources;

use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Module $resource
 */
class ModuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $module = $this->resource;

        return [
            'id' => $module->id,
            'code' => $module->code,
            'name' => $module->name,
            'color_code' => $module->color_code,
            'total_hours' => $module->total_hours,
            'lecture_hours' => $module->lecture_hours,
            'tp_hours' => $module->tp_hours,
            'continuous_assessment_weight' => $module->continuous_assessment_weight,
            'description' => $module->description,
            'is_active' => (bool) $module->is_active,
        ];
    }
}
