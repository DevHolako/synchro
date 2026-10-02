<?php

namespace App\Actions\Users;

use App\Models\User;

/**
 * Every teacher as a picker option, by name.
 */
class ListTeacherOptionsAction
{
    /**
     * @return list<array{id: int, name: string}>
     */
    public function execute(): array
    {
        return array_values(User::teachers()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $teacher): array => ['id' => $teacher->id, 'name' => $teacher->name])
            ->all());
    }
}
