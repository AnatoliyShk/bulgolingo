<?php

namespace App\Policies;

use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Learning paths are reached from two sides. Students open, start and restart
 * a single path, which `view` decides by the catalog's type visibility. The
 * admin panel lists and edits every path, which follows the panel's own rules:
 * `EnsureIsAdmin` lets admins and admin visitors in, and `RestrictAdminVisitor`
 * keeps visitors read-only. The public catalog needs no ability of its own,
 * since `LearningPath::visibleTo()` already filters it for guests too.
 */
class LearningPathPolicy
{
    /**
     * The admin list, which shows every type, `test` paths included, to
     * admins and admin visitors.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * A student-facing path is open to whoever the catalog would show it to,
     * a guest included. A hidden path is a 404 rather than a 403, because
     * saying the id exists is more than the catalog was willing to show.
     */
    public function view(?User $user, LearningPath $learningPath): Response
    {
        return $learningPath->isVisibleTo($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Only an admin may add a path; a visitor's access is read-only.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may change a path or its lessons; a visitor's access is
     * read-only.
     */
    public function update(User $user, LearningPath $learningPath): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may delete a path. Learning paths are not soft-deleted, so
     * there are no restore or force-delete abilities.
     */
    public function delete(User $user, LearningPath $learningPath): bool
    {
        return $user->isAdmin();
    }
}
