<?php

namespace App\Policies;

use App\Models\Lexema;
use App\Models\User;

/**
 * Lexemas are shared course vocabulary, managed from the admin panel, so this
 * policy follows the panel's own access rules: `EnsureIsAdmin` lets admins and
 * admin visitors in, and `RestrictAdminVisitor` keeps visitors read-only.
 * Students never manage lexemas; their own review state lives in `user_lexema`
 * and reaches them through the stats page, not through this model.
 */
class LexemaPolicy
{
    /**
     * Admins and admin visitors may browse the vocabulary, which carries no
     * user data, unlike the user and messenger lists hidden from visitors.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * Viewing one lexema follows the same rule as the list.
     */
    public function view(User $user, Lexema $lexema): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * Only an admin may add a word; a visitor's access is read-only.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may change a word; a visitor's access is read-only.
     */
    public function update(User $user, Lexema $lexema): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may delete a word. The delete cascades to its
     * `exercise_lexema` links and every learner's `review_logs` for it, so it
     * is not something a read-only visitor can be allowed near. Lexemas are
     * not soft-deleted, so there are no restore or force-delete abilities.
     */
    public function delete(User $user, Lexema $lexema): bool
    {
        return $user->isAdmin();
    }
}
