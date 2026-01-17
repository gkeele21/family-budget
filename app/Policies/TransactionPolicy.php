<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function update(User $user, Transaction $transaction): bool
    {
        return $user->currentBudget && $transaction->budget_id === $user->currentBudget->id;
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->currentBudget && $transaction->budget_id === $user->currentBudget->id;
    }
}
