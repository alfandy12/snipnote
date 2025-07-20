<?php

namespace App\Jobs;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificationFromSharingNote implements ShouldQueue
{
    use Queueable;

    public $userIds = [], $notification;
    /**
     * Create a new job instance.
     */
    public function __construct(array $userIds, $notification)
    {
        $this->userIds = $userIds;
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // handle memory if large data
        User::whereIn('id', $this->userIds)
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    $user->notify($this->notification);
                }
            });
    }
}
