<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Note $note): bool
    {
        // Check if the user is in the note's users or if the note is public
        // This allows users to view notes they have access to or public notes
        return $note->users->contains($user) || $note->is_public;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Note $note): bool
    {
        return $note->users->contains(function ($allowedUser) use ($user) {
            return $allowedUser->id === $user->id && $allowedUser->pivot->is_owner;
        });
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Note $note): bool
    {
        return $note->users->contains(function ($allowedUser) use ($user) {
            return $allowedUser->id === $user->id && $allowedUser->pivot->is_owner;
        });
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Note $note): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Note $note): bool
    {
        return false;
    }
}
