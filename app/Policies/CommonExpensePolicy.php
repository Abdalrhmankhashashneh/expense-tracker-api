<?php

namespace App\Policies;

use App\Models\CommonExpense;
use App\Models\User;

class CommonExpensePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view common expenses');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CommonExpense $commonExpense): bool
    {
        return $user->can('view common expenses') && $user->id === $commonExpense->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create common expense');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CommonExpense $commonExpense): bool
    {
        return $user->can('update common expense') && $user->id === $commonExpense->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CommonExpense $commonExpense): bool
    {
        return $user->can('delete common expense') && $user->id === $commonExpense->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CommonExpense $commonExpense): bool
    {
        return $user->can('delete common expense') && $user->id === $commonExpense->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CommonExpense $commonExpense): bool
    {
        return $user->can('delete common expense') && $user->id === $commonExpense->user_id;
    }

    /**
     * Determine whether the user can apply (use) a common expense template.
     * Checks 'create expense' permission since applying creates a real expense.
     */
    public function apply(User $user, CommonExpense $commonExpense): bool
    {
        return $user->can('create expense') && $user->id === $commonExpense->user_id;
    }
}
