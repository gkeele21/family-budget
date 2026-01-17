<?php

namespace App\Policies;

use App\Models\CategoryGroup;
use App\Models\User;

class CategoryGroupPolicy
{
    public function update(User $user, CategoryGroup $categoryGroup): bool
    {
        return $user->currentBudget && $categoryGroup->budget_id === $user->currentBudget->id;
    }

    public function delete(User $user, CategoryGroup $categoryGroup): bool
    {
        return $user->currentBudget && $categoryGroup->budget_id === $user->currentBudget->id;
    }
}
