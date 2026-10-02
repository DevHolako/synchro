<?php

namespace App\Actions\Notifications;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class ListRecentNotificationsAction
{
    /**
     * @return array{
     *     notifications: list<array{
     *         id: string,
     *         data: array<string, mixed>,
     *         read_at: string|null,
     *         created_at: string
     *     }>,
     *     unread_count: int
     * }
     */
    public function execute(User $user, int $limit = 15): array
    {
        $notifications = $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'data' => (array) $n->data,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String() ?? now()->toIso8601String(),
            ])
            ->all();

        return [
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }
}
