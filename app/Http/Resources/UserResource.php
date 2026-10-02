<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource;

        $profile = null;
        if ($user->studentProfile !== null) {
            $group = $user->studentProfile->studentGroup;
            $profile = [
                'type' => 'student',
                'matricule' => $user->studentProfile->matricule,
                'student_group_id' => $user->studentProfile->student_group_id,
                'student_group' => $group !== null ? [
                    'id' => $group->id,
                    'name' => $group->name,
                    'code' => $group->code,
                ] : null,
            ];
        } elseif ($user->teacherProfile !== null) {
            $profile = [
                'type' => 'teacher',
                'matricule' => $user->teacherProfile->matricule,
                'title' => $user->teacherProfile->title,
            ];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'permissions' => $user->permissionValues(),
            'profile' => $profile,
            'unread_notifications_count' => $user->unreadNotifications()->count(),
        ];
    }
}
