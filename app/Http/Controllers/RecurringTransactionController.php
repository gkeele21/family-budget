<?php

namespace App\Http\Controllers;

use App\Models\Payee;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RecurringTransactionController extends Controller
{
    public function index()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('budgets.create');
        }

        $recurring = $budget->recurringTransactions()
            ->with(['account', 'category', 'payee'])
            ->orderBy('next_date')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'payee' => $r->payee?->name ?? 'Unknown',
                'account' => $r->account->name,
                'category' => $r->category?->name,
                'amount' => (float) $r->amount,
                'type' => $r->type,
                'frequency' => $r->frequency,
                'next_date' => $r->next_date->format('Y-m-d'),
                'is_active' => $r->is_active,
            ]);

        return Inertia::render('Settings/Recurring/Index', [
            'recurring' => $recurring,
        ]);
    }

    public function create()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('budgets.create');
        }

        $accounts = $budget->accounts()
            ->where('is_closed', false)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type']);

        $categories = $budget->categoryGroups()
            ->with(['categories' => fn($q) => $q->where('is_hidden', false)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'categories' => $group->categories->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'icon' => $c->icon,
                ]),
            ]);

        $payees = $budget->payees()->orderBy('name')->get(['id', 'name', 'default_category_id']);

        return Inertia::render('Settings/Recurring/Create', [
            'accounts' => $accounts,
            'categories' => $categories,
            'payees' => $payees,
        ]);
    }

    public function store(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('budgets.create');
        }

        $validated = $request->validate([
            'type' => 'required|in:expense,income',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'payee_name' => 'nullable|string|max:255',
            'frequency' => 'required|in:daily,weekly,biweekly,monthly,yearly',
            'next_date' => 'required|date',
            'end_date' => 'nullable|date|after:next_date',
        ]);

        // Handle payee
        $payeeId = null;
        if ($validated['payee_name']) {
            $payee = Payee::firstOrCreate(
                ['budget_id' => $budget->id, 'name' => $validated['payee_name']],
                ['default_category_id' => $validated['category_id']]
            );
            $payeeId = $payee->id;
        }

        RecurringTransaction::create([
            'budget_id' => $budget->id,
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'],
            'payee_id' => $payeeId,
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'next_date' => $validated['next_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('recurring.index');
    }

    public function edit(RecurringTransaction $recurring)
    {
        $budget = Auth::user()->currentBudget;

        if ($recurring->budget_id !== $budget->id) {
            abort(403);
        }

        $accounts = $budget->accounts()
            ->where('is_closed', false)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type']);

        $categories = $budget->categoryGroups()
            ->with(['categories' => fn($q) => $q->where('is_hidden', false)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'categories' => $group->categories->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'icon' => $c->icon,
                ]),
            ]);

        $payees = $budget->payees()->orderBy('name')->get(['id', 'name', 'default_category_id']);

        return Inertia::render('Settings/Recurring/Edit', [
            'recurring' => [
                'id' => $recurring->id,
                'type' => $recurring->type,
                'amount' => abs((float) $recurring->amount),
                'account_id' => $recurring->account_id,
                'category_id' => $recurring->category_id,
                'payee_name' => $recurring->payee?->name,
                'frequency' => $recurring->frequency,
                'next_date' => $recurring->next_date->format('Y-m-d'),
                'end_date' => $recurring->end_date?->format('Y-m-d'),
                'is_active' => $recurring->is_active,
            ],
            'accounts' => $accounts,
            'categories' => $categories,
            'payees' => $payees,
        ]);
    }

    public function update(Request $request, RecurringTransaction $recurring)
    {
        $budget = Auth::user()->currentBudget;

        if ($recurring->budget_id !== $budget->id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:expense,income',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'payee_name' => 'nullable|string|max:255',
            'frequency' => 'required|in:daily,weekly,biweekly,monthly,yearly',
            'next_date' => 'required|date',
            'end_date' => 'nullable|date|after:next_date',
            'is_active' => 'boolean',
        ]);

        // Handle payee
        $payeeId = null;
        if ($validated['payee_name']) {
            $payee = Payee::firstOrCreate(
                ['budget_id' => $budget->id, 'name' => $validated['payee_name']],
                ['default_category_id' => $validated['category_id']]
            );
            $payeeId = $payee->id;
        }

        $recurring->update([
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'],
            'payee_id' => $payeeId,
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'next_date' => $validated['next_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('recurring.index');
    }

    public function destroy(RecurringTransaction $recurring)
    {
        $budget = Auth::user()->currentBudget;

        if ($recurring->budget_id !== $budget->id) {
            abort(403);
        }

        $recurring->delete();

        return redirect()->route('recurring.index');
    }

    public function toggleActive(RecurringTransaction $recurring)
    {
        $budget = Auth::user()->currentBudget;

        if ($recurring->budget_id !== $budget->id) {
            abort(403);
        }

        $recurring->update(['is_active' => !$recurring->is_active]);

        return back();
    }

    /**
     * Process all due recurring transactions.
     * This can be called via artisan command or manually triggered.
     */
    public static function processDueTransactions(): int
    {
        $count = 0;
        $today = now()->startOfDay();

        $dueRecurring = RecurringTransaction::with(['account', 'budget'])
            ->where('is_active', true)
            ->where('next_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            })
            ->get();

        foreach ($dueRecurring as $recurring) {
            // Create the transaction
            $amount = $recurring->type === 'expense'
                ? -abs($recurring->amount)
                : abs($recurring->amount);

            // Auto-clear cash account transactions
            $cleared = $recurring->account->type === 'cash';

            Transaction::create([
                'budget_id' => $recurring->budget_id,
                'account_id' => $recurring->account_id,
                'category_id' => $recurring->category_id,
                'payee_id' => $recurring->payee_id,
                'amount' => $amount,
                'type' => $recurring->type,
                'date' => $recurring->next_date,
                'cleared' => $cleared,
                'recurring_id' => $recurring->id,
                'created_by' => $recurring->budget->owner_id,
            ]);

            // Calculate next date
            $nextDate = match ($recurring->frequency) {
                'daily' => $recurring->next_date->addDay(),
                'weekly' => $recurring->next_date->addWeek(),
                'biweekly' => $recurring->next_date->addWeeks(2),
                'monthly' => $recurring->next_date->addMonth(),
                'yearly' => $recurring->next_date->addYear(),
            };

            // Check if we should deactivate due to end_date
            if ($recurring->end_date && $nextDate->gt($recurring->end_date)) {
                $recurring->update(['is_active' => false, 'next_date' => $nextDate]);
            } else {
                $recurring->update(['next_date' => $nextDate]);
            }

            $count++;
        }

        return $count;
    }
}
