<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Log a meaningful user action.
     */
    protected function logActivity(string $activity): void
    {
        $user = auth()->user();
        if ($user) {
            ActivityLog::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'activity' => $activity,
            ]);
        }
    }
}
