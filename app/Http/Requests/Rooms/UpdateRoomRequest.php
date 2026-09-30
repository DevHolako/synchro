<?php

namespace App\Http\Requests\Rooms;

use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Room $room */
        $room = $this->route('room');

        return $this->user()?->can('update', $room) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Room $room */
        $room = $this->route('room');
        $buildingId = $this->input('building_id', $room->building_id);
        $courseCapacity = $this->input('course_capacity', $room->course_capacity);

        return [
            'building_id' => ['sometimes', 'required', 'integer', 'exists:buildings,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('rooms', 'name')
                    ->where('building_id', $buildingId)
                    ->ignore($room->id),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'floor' => ['nullable', 'integer', 'between:-5,50'],
            'course_capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'exam_capacity' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'lte:'.$courseCapacity,
            ],
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
            'exam_capacity.lte' => 'The exam capacity cannot exceed the course capacity.',
            'name.unique' => 'A room with this name already exists in the selected building.',
        ];
    }
}
