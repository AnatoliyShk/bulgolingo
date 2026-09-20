<?php

namespace App\Contracts;

use App\Models\Lesson;
use App\Models\User;

interface ExerciseInterface
{
    /**
     * Records that $user completed this exercise and returns the id of the
     * next incomplete exercise in $lesson, or null when there is no lesson or
     * nothing is left in it.
     */
    public function completeFor(User $user, ?Lesson $lesson): ?int;

    /**
     * The normalized words this exercise teaches, used to track lexemas.
     *
     * @return array<int, string>
     */
    public function getExerciseWords(): array;
}
