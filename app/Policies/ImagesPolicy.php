<?php

namespace App\Policies;

use App\Models\Images;
use App\Models\User;

/**
 * Exercise images are uploaded, replaced and deleted from the admin exercise
 * form, so this policy follows the admin panel's access rules: `EnsureIsAdmin`
 * lets admins and admin visitors in, and `RestrictAdminVisitor` keeps visitors
 * read-only. Students see an image only inside the exercise player, through
 * the signed URL the exercise carries, which does not go through this policy.
 */
class ImagesPolicy
{
    /**
     * Admins and admin visitors may browse the images.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * Viewing one image follows the same rule as the list.
     */
    public function view(User $user, Images $images): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * Only an admin may upload an image; a visitor's access is read-only.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may change an image; a visitor's access is read-only.
     */
    public function update(User $user, Images $images): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only an admin may delete an image, which also removes its file from the
     * bucket. Images are not soft-deleted, so there are no restore or
     * force-delete abilities.
     */
    public function delete(User $user, Images $images): bool
    {
        return $user->isAdmin();
    }
}
