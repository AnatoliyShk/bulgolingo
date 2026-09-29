<?php

namespace App\Policies;

use App\Models\ScriptedDialogue;
use App\Models\User;

/**
 * Scripted dialogues are authored in the admin panel only, so this policy
 * follows the panel's own access rules: `EnsureIsAdmin` lets admins and admin
 * visitors in, and `RestrictAdminVisitor` keeps visitors read-only. Students
 * have no access yet; a student-facing action will need its own rule here.
 */
class ScriptedDialoguePolicy
{
    /**
     * Admins and admin visitors may browse the dialogues.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * Viewing one dialogue follows the same rule as the list.
     */
    public function view(User $user, ScriptedDialogue $scriptedDialogue): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * Only an admin may add a dialogue; a visitor's access is read-only.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may change a dialogue; a visitor's access is read-only.
     */
    public function update(User $user, ScriptedDialogue $scriptedDialogue): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may delete a dialogue. Dialogues are not soft-deleted, so
     * there are no restore or force-delete abilities.
     */
    public function delete(User $user, ScriptedDialogue $scriptedDialogue): bool
    {
        return $user->isAdmin();
    }
}
