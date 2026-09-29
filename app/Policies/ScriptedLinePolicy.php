<?php

namespace App\Policies;

use App\Models\ScriptedLine;
use App\Models\User;

/**
 * Scripted lines belong to scripted dialogues and are authored alongside them
 * in the admin panel, so this policy follows the panel's own access rules:
 * `EnsureIsAdmin` lets admins and admin visitors in, and
 * `RestrictAdminVisitor` keeps visitors read-only. Students have no access
 * yet; a student-facing action will need its own rule here.
 */
class ScriptedLinePolicy
{
    /**
     * Admins and admin visitors may browse the lines.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * Viewing one line follows the same rule as the list.
     */
    public function view(User $user, ScriptedLine $scriptedLine): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * Only an admin may add a line; a visitor's access is read-only.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may change a line; a visitor's access is read-only.
     */
    public function update(User $user, ScriptedLine $scriptedLine): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may delete a line. Lines are not soft-deleted, so there
     * are no restore or force-delete abilities.
     */
    public function delete(User $user, ScriptedLine $scriptedLine): bool
    {
        return $user->isAdmin();
    }
}
