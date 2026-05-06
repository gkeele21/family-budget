<?php

namespace App\Http\Controllers;

use App\Models\Payee;
use App\Models\RecurringTransaction;
use App\Models\SplitTransaction;
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
            return redirect()->route('onboarding.setup');
        }

        // Preload all category names for resolving from JSON
        $categoryNames = $budget->categoryGroups()
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->pluck('name', 'id');

        $recurring = $budget->recurringTransactions()
            ->with(['account', 'payee'])
            ->orderBy('next_date')
            ->get()
            ->map(function ($r) use ($categoryNames) {
                $categories = $r->categories ?? [];
                $isSplit = $r->isSplit();

                if ($isSplit) {
                    $categoryDisplay = 'Split (' . count($categories) . ')';
                } elseif (!empty($categories)) {
                    $categoryDisplay = $categoryNames[$categories[0]['category_id']] ?? null;
                } else {
                    $categoryDisplay = null;
                }

                $splits = $isSplit ? collect($categories)->map(fn($c) => [
                    'category' => $c['category_id'] ? ($categoryNames[$c['category_id']] ?? null) : null,
                    'amount' => (float) $c['amount'],
                ]) : null;

                return [
                    'id' => $r->id,
                    'payee' => $r->payee?->name ?? 'Unknown',
                    'account' => $r->account->name,
                    'category' => $categoryDisplay,
                    'is_split' => $isSplit,
                    'splits' => $splits,
                    'amount' => (float) $r->amount,
                    'type' => $r->type,
                    'frequency' => $r->frequency,
                    'next_date' => $r->next_date->format('Y-m-d'),
                    'is_active' => $r->is_active,
                ];
            });

        return Inertia::render('Settings/Recurring/Index', [
            'recurring' => $recurring,
        ]);
    }

    public function create()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
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
            return redirect()->route('onboarding.setup');
        }

        $validated = $request->validate([
            'type' => 'required|in:expense,income',
            'amount' => 'required|numeric|not_in:0',
            'account_id' => 'required|exists:accounts,id',
            'categories' => 'nullable|array',
            'categories.*.category_id' => 'nullable|exists:categories,id',
            'categories.*.amount' => 'required_with:categories|numeric',
            'payee_name' => 'nullable|string|max:255',
            'frequency' => 'required|in:daily,weekly,biweekly,monthly,yearly',
            'next_date' => 'required|date',
            'end_date' => 'nullable|date|after:next_date',
        ]);

        // Handle payee
        $payeeId = null;
        $defaultCategoryId = !empty($validated['categories']) ? $validated['categories'][0]['category_id'] : null;
        if ($validated['payee_name']) {
            $payee = Payee::firstOrCreate(
                ['budget_id' => $budget->id, 'name' => $validated['payee_name']],
                ['default_category_id' => $defaultCategoryId]
            );
            $payeeId = $payee->id;
        }

        $amount = (float) $validated['amount'];
        if ($validated['type'] === 'expense') {
            $amount = -abs($amount);
        } elseif ($validated['type'] === 'income') {
            $amount = abs($amount);
        }

        RecurringTransaction::create([
            'budget_id' => $budget->id,
            'account_id' => $validated['account_id'],
            'categories' => $validated['categories'] ?? null,
            'payee_id' => $payeeId,
            'amount' => $amount,
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
                'amount' => (float) $recurring->amount,
                'account_id' => $recurring->account_id,
                'categories' => $recurring->categories,
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
            'amount' => 'required|numeric|not_in:0',
            'account_id' => 'required|exists:accounts,id',
            'categories' => 'nullable|array',
            'categories.*.category_id' => 'nullable|exists:categories,id',
            'categories.*.amount' => 'required_with:categories|numeric',
            'payee_name' => 'nullable|string|max:255',
            'frequency' => 'required|in:daily,weekly,biweekly,monthly,yearly',
            'next_date' => 'required|date',
            'end_date' => 'nullable|date|after:next_date',
            'is_active' => 'boolean',
        ]);

        // Handle payee
        $payeeId = null;
        $defaultCategoryId = !empty($validated['categories']) ? $validated['categories'][0]['category_id'] : null;
        if ($validated['payee_name']) {
            $payee = Payee::firstOrCreate(
                ['budget_id' => $budget->id, 'name' => $validated['payee_name']],
                ['default_category_id' => $defaultCategoryId]
            );
            $payeeId = $payee->id;
        }

        $amount = (float) $validated['amount'];
        if ($validated['type'] === 'expense') {
            $amount = -abs($amount);
        } elseif ($validated['type'] === 'income') {
            $amount = abs($amount);
        }

        $recurring->update([
            'account_id' => $validated['account_id'],
            'categories' => $validated['categories'] ?? null,
            'payee_id' => $payeeId,
            'amount' => $amount,
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
     * Post a single due recurring transaction.
     */
    public function post(RecurringTransaction $recurring)
    {
        $budget = Auth::user()->currentBudget;
        abort_unless($recurring->budget_id === $budget->id, 403);

        $today = now()->startOfDay();
        abort_unless($recurring->is_active && $recurring->next_date->lte($today), 422);

        $this->createTransactionFromRecurring($recurring);

        return redirect()->route('transactions.index');
    }

    /**
     * Post all due recurring transactions for the current budget.
     */
    public function postAll()
    {
        $budget = Auth::user()->currentBudget;
        $today = now()->startOfDay();

        $dueRecurring = RecurringTransaction::with(['account', 'budget'])
            ->where('budget_id', $budget->id)
            ->where('is_active', true)
            ->where('next_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            })
            ->get();

        foreach ($dueRecurring as $recurring) {
            $this->createTransactionFromRecurring($recurring);
        }

        return redirect()->route('transactions.index');
    }

    /**
     * Create a transaction from a recurring template and advance its next_date.
     */
    private function createTransactionFromRecurring(RecurringTransaction $recurring): void
    {
        $amount = (float) $recurring->amount;
        if ($recurring->type === 'expense') {
            $amount = -abs($amount);
        } elseif ($recurring->type === 'income') {
            $amount = abs($amount);
        }

        // Auto-clear only cash account transactions
        $cleared = $recurring->account->type === 'cash';

        // Capture date before advancing (Carbon is mutable)
        $transactionDate = $recurring->next_date->copy();

        // Determine category_id: null if split, otherwise first category
        $categoryId = $recurring->isSplit() ? null : $recurring->primaryCategoryId();

        $transaction = Transaction::create([
            'budget_id' => $recurring->budget_id,
            'account_id' => $recurring->account_id,
            'category_id' => $categoryId,
            'payee_id' => $recurring->payee_id,
            'amount' => $amount,
            'type' => $recurring->type,
            'date' => $transactionDate,
            'cleared' => $cleared,
            'recurring_id' => $recurring->id,
            'created_by' => $recurring->budget->owner_id,
        ]);

        // Create split transactions if split
        if ($recurring->isSplit()) {
            foreach ($recurring->categories as $split) {
                SplitTransaction::create([
                    'transaction_id' => $transaction->id,
                    'category_id' => $split['category_id'] ?: null,
                    'amount' => (float) $split['amount'],
                ]);
            }
        }

        // Calculate next date
        $nextDate = match ($recurring->frequency) {
            'daily' => $recurring->next_date->copy()->addDay(),
            'weekly' => $recurring->next_date->copy()->addWeek(),
            'biweekly' => $recurring->next_date->copy()->addWeeks(2),
            'monthly' => $recurring->next_date->copy()->addMonth(),
            'yearly' => $recurring->next_date->copy()->addYear(),
        };

        if ($recurring->end_date && $nextDate->gt($recurring->end_date)) {
            $recurring->update(['is_active' => false, 'next_date' => $nextDate]);
        } else {
            $recurring->update(['next_date' => $nextDate]);
        }
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

        $controller = new self();
        foreach ($dueRecurring as $recurring) {
            $controller->createTransactionFromRecurring($recurring);
            $count++;
        }

        return $count;
    }
}
