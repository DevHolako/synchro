<?php

namespace App\Http\Requests\Rooms;

use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Room::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('rooms', 'name')->where('building_id', $this->input('building_id')),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'floor' => ['nullable', 'integer', 'between:-5,50'],
            'course_capacity' => ['required', 'integer', 'min:1'],
            'exam_capacity' => ['required', 'integer', 'min:1', 'lte:course_capacity'],
            'has_projector' => ['boolean'],
            'is_lab' => ['boolean'],
            'has_computers' => ['boolean'],
            'has_sound_system' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'exam_capacity.lte' => __('messages.room_exam_capacity_exceeds_course'),
            'name.unique' => __('messages.room_name_taken'),
        ];
    }
}
