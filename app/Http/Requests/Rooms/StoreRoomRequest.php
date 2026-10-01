<?php

namespace App\Http\Requests\Rooms;

use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    use ReadsTypedInput;

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

    /**
     * The validated input, typed for the action.
     *
     * @return array{building_id: int, name: string, code: string|null, floor: int|null, course_capacity: int, exam_capacity: int, has_projector: bool, is_lab: bool, has_computers: bool, has_sound_system: bool, is_active?: bool}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'building_id' => $this->integer('building_id'),
            'name' => $this->string('name')->value(),
            'code' => $this->nullableString('code'),
            'floor' => $this->nullableInteger('floor'),
            'course_capacity' => $this->integer('course_capacity'),
            'exam_capacity' => $this->integer('exam_capacity'),
            'has_projector' => $this->boolean('has_projector'),
            'is_lab' => $this->boolean('is_lab'),
            'has_computers' => $this->boolean('has_computers'),
            'has_sound_system' => $this->boolean('has_sound_system'),
            ...$this->activeFlag(),
        ];
    }
}
