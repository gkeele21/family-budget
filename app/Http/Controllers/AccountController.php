<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        $accounts = $budget->accounts()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'starting_balance' => (float) $account->starting_balance,
                'balance' => $account->balance,
                'is_closed' => $account->is_closed,
            ]);

        return Inertia::render('Settings/Accounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Settings/Accounts/Create');
    }

    public function store(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:checking,savings,credit_card,cash',
            'starting_balance' => 'required|numeric',
        ]);

        $maxOrder = $budget->accounts()->max('sort_order') ?? 0;

        Account::create([
            'budget_id' => $budget->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'starting_balance' => $validated['starting_balance'],
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('settings.accounts');
    }

    public function edit(Account $account)
    {
        $this->authorize('update', $account);

        return Inertia::render('Settings/Accounts/Edit', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'starting_balance' => (float) $account->starting_balance,
                'is_closed' => $account->is_closed,
            ],
        ]);
    }

    public function update(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:checking,savings,credit_card,cash',
            'starting_balance' => 'required|numeric',
            'is_closed' => 'boolean',
        ]);

        $account->update($validated);

        return redirect()->route('settings.accounts');
    }

    public function destroy(Account $account)
    {
        $this->authorize('delete', $account);

        $account->delete();

        return redirect()->route('settings.accounts');
    }

    public function reorder(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:accounts,id',
        ]);

        $accountIds = $budget->accounts()->pluck('id')->toArray();
        foreach ($validated['ids'] as $id) {
            if (!in_array($id, $accountIds)) {
                abort(403);
            }
        }

        foreach ($validated['ids'] as $index => $id) {
            Account::where('id', $id)->update(['sort_order' => $index]);
        }

        return back();
    }
}
