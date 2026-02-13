<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Payee;
use App\Models\SplitTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('budgets.create');
        }

        $accountFilter = $request->get('account');
        $searchQuery = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $clearedFilter = $request->get('cleared'); // 'all', 'cleared', 'uncleared'
        $recurringFilter = $request->get('recurring'); // 'all', 'recurring'

        $query = $budget->transactions()
            ->with(['account', 'category', 'payee', 'splits.category', 'transferPair.account'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($accountFilter) {
            $query->where('account_id', $accountFilter);
        } else {
            // When viewing all accounts, consolidate transfers into one row
            // Keep only the outflow side (negative amount) of each transfer pair
            $query->where(function ($q) {
                $q->where('type', '!=', 'transfer')
                  ->orWhere('amount', '<', 0);
            });
        }

        // Date range filter
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        // Cleared status filter
        if ($clearedFilter === 'cleared') {
            $query->where('cleared', true);
        } elseif ($clearedFilter === 'uncleared') {
            $query->where('cleared', false);
        }

        // Recurring filter
        if ($recurringFilter === 'recurring') {
            $query->whereNotNull('recurring_id');
        }

        // Search functionality
        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                // Search by payee name
                $q->whereHas('payee', function ($pq) use ($searchQuery) {
                    $pq->where('name', 'like', "%{$searchQuery}%");
                })
                // Search by memo
                ->orWhere('memo', 'like', "%{$searchQuery}%")
                // Search by amount (exact or partial)
                ->orWhere('amount', 'like', "%{$searchQuery}%")
                // Search by category name
                ->orWhereHas('category', function ($cq) use ($searchQuery) {
                    $cq->where('name', 'like', "%{$searchQuery}%");
                });
            });
        }

        $transactions = $query->get()->map(fn($t) => [
            'id' => $t->id,
            'date' => $t->date->format('Y-m-d'),
            'payee' => $t->type === 'transfer'
                ? ($t->amount < 0
                    ? $t->account->name . ' → ' . ($t->transferPair?->account?->name ?? 'Unknown')
                    : ($t->transferPair?->account?->name ?? 'Unknown') . ' → ' . $t->account->name)
                : ($t->payee?->name ?? 'Unknown'),
            'account' => $t->account->name,
            'account_id' => $t->account_id,
            'category' => $t->isSplit()
                ? 'Split (' . $t->splits->count() . ')'
                : ($t->category?->name ?? null),
            'category_id' => $t->category_id,
            'amount' => (float) $t->amount,
            'type' => $t->type,
            'cleared' => $t->cleared,
            'memo' => $t->memo,
            'recurring_id' => $t->recurring_id,
            'is_split' => $t->isSplit(),
            'splits' => $t->isSplit() ? $t->splits->map(fn($s) => [
                'category' => $s->category?->name,
                'category_id' => $s->category_id,
                'amount' => (float) $s->amount,
            ]) : [],
        ]);

        // Group by date
        $groupedTransactions = $transactions->groupBy('date');

        $accounts = $budget->accounts()
            ->where('is_closed', false)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type']);

        // Load recurring transactions for the "Recurring" tab
        $recurringTransactions = $budget->recurringTransactions()
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

        return Inertia::render('Transactions/Index', [
            'transactions' => $groupedTransactions,
            'accounts' => $accounts,
            'currentAccountId' => $accountFilter ? (int) $accountFilter : null,
            'searchQuery' => $searchQuery,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'clearedFilter' => $clearedFilter ?? 'all',
            'recurringFilter' => $recurringFilter ?? 'all',
            'recurring' => $recurringTransactions,
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

        $payees = $budget->payees()
            ->orderBy('name')
            ->get(['id', 'name', 'default_category_id']);

        return Inertia::render('Transactions/Create', [
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
            'type' => 'required|in:expense,income,transfer',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'payee_name' => 'nullable|string|max:255',
            'date' => 'required|date',
            'cleared' => 'boolean',
            'memo' => 'nullable|string|max:500',
            'to_account_id' => 'required_if:type,transfer|nullable|exists:accounts,id',
            'is_split' => 'boolean',
            'splits' => 'array',
            'splits.*.category_id' => 'required_with:splits|exists:categories,id',
            'splits.*.amount' => 'required_with:splits|numeric|min:0.01',
            'update_payee_default' => 'boolean',
        ]);

        $user = Auth::user();

        // Handle payee
        $payeeId = null;
        if ($validated['payee_name'] && $validated['type'] !== 'transfer') {
            $categoryForDefault = $validated['category_id'] ?? ($validated['splits'][0]['category_id'] ?? null);
            $payee = Payee::firstOrCreate(
                ['budget_id' => $budget->id, 'name' => $validated['payee_name']],
                ['default_category_id' => $categoryForDefault]
            );
            $payeeId = $payee->id;

            // Update payee default category if requested
            if (($validated['update_payee_default'] ?? false) && $categoryForDefault) {
                $payee->update(['default_category_id' => $categoryForDefault]);
            }
        }

        // Calculate amount (negative for expenses)
        $amount = $validated['amount'];
        if ($validated['type'] === 'expense') {
            $amount = -abs($amount);
        }

        // Auto-clear cash account transactions
        $account = Account::find($validated['account_id']);
        $cleared = $account->type === 'cash' ? true : ($validated['cleared'] ?? false);

        if ($validated['type'] === 'transfer') {
            $toAccount = Account::find($validated['to_account_id']);
            $fromCleared = $account->type === 'cash' ? true : ($validated['cleared'] ?? false);
            $toCleared = $toAccount->type === 'cash' ? true : ($validated['cleared'] ?? false);

            // Create two linked transactions
            $fromTransaction = Transaction::create([
                'budget_id' => $budget->id,
                'account_id' => $validated['account_id'],
                'category_id' => null,
                'payee_id' => null,
                'amount' => -abs($validated['amount']),
                'type' => 'transfer',
                'date' => $validated['date'],
                'cleared' => $fromCleared,
                'memo' => $validated['memo'],
                'created_by' => $user->id,
            ]);

            $toTransaction = Transaction::create([
                'budget_id' => $budget->id,
                'account_id' => $validated['to_account_id'],
                'category_id' => null,
                'payee_id' => null,
                'amount' => abs($validated['amount']),
                'type' => 'transfer',
                'date' => $validated['date'],
                'cleared' => $toCleared,
                'memo' => $validated['memo'],
                'transfer_pair_id' => $fromTransaction->id,
                'created_by' => $user->id,
            ]);

            $fromTransaction->update(['transfer_pair_id' => $toTransaction->id]);
        } else {
            DB::transaction(function () use ($validated, $budget, $payeeId, $amount, $user, $cleared) {
                // Create the main transaction
                $transaction = Transaction::create([
                    'budget_id' => $budget->id,
                    'account_id' => $validated['account_id'],
                    'category_id' => ($validated['is_split'] ?? false) ? null : $validated['category_id'],
                    'payee_id' => $payeeId,
                    'amount' => $amount,
                    'type' => $validated['type'],
                    'date' => $validated['date'],
                    'cleared' => $cleared,
                    'memo' => $validated['memo'],
                    'created_by' => $user->id,
                ]);

                // Create split transactions if this is a split
                if (($validated['is_split'] ?? false) && !empty($validated['splits'])) {
                    foreach ($validated['splits'] as $split) {
                        $splitAmount = $validated['type'] === 'expense'
                            ? -abs($split['amount'])
                            : abs($split['amount']);

                        SplitTransaction::create([
                            'transaction_id' => $transaction->id,
                            'category_id' => $split['category_id'],
                            'amount' => $splitAmount,
                        ]);
                    }
                }
            });
        }

        return redirect()->route('transactions.index');
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $budget = Auth::user()->currentBudget;

        // Load splits
        $transaction->load('splits');

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

        return Inertia::render('Transactions/Edit', [
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => abs((float) $transaction->amount),
                'account_id' => $transaction->account_id,
                'category_id' => $transaction->category_id,
                'payee_name' => $transaction->payee?->name,
                'date' => $transaction->date->format('Y-m-d'),
                'cleared' => $transaction->cleared,
                'memo' => $transaction->memo,
                'transfer_pair_id' => $transaction->transfer_pair_id,
                'is_split' => $transaction->isSplit(),
                'splits' => $transaction->splits->map(fn($s) => [
                    'category_id' => $s->category_id,
                    'amount' => abs((float) $s->amount),
                ]),
            ],
            'accounts' => $accounts,
            'categories' => $categories,
            'payees' => $payees,
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $budget = Auth::user()->currentBudget;

        $validated = $request->validate([
            'type' => 'required|in:expense,income,transfer',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'payee_name' => 'nullable|string|max:255',
            'date' => 'required|date',
            'cleared' => 'boolean',
            'memo' => 'nullable|string|max:500',
            'is_split' => 'boolean',
            'splits' => 'array',
            'splits.*.category_id' => 'required_with:splits|exists:categories,id',
            'splits.*.amount' => 'required_with:splits|numeric|min:0.01',
        ]);

        // Handle payee
        $payeeId = null;
        if ($validated['payee_name'] && $validated['type'] !== 'transfer') {
            $payee = Payee::firstOrCreate(
                ['budget_id' => $budget->id, 'name' => $validated['payee_name']],
                ['default_category_id' => $validated['category_id'] ?? ($validated['splits'][0]['category_id'] ?? null)]
            );
            $payeeId = $payee->id;
        }

        // Calculate amount
        $amount = $validated['amount'];
        if ($validated['type'] === 'expense') {
            $amount = -abs($amount);
        }

        DB::transaction(function () use ($validated, $transaction, $payeeId, $amount) {
            $transaction->update([
                'account_id' => $validated['account_id'],
                'category_id' => ($validated['is_split'] ?? false) ? null : $validated['category_id'],
                'payee_id' => $payeeId,
                'amount' => $amount,
                'type' => $validated['type'],
                'date' => $validated['date'],
                'cleared' => $validated['cleared'] ?? false,
                'memo' => $validated['memo'],
            ]);

            // Delete existing splits
            $transaction->splits()->delete();

            // Create new splits if this is a split transaction
            if (($validated['is_split'] ?? false) && !empty($validated['splits'])) {
                foreach ($validated['splits'] as $split) {
                    $splitAmount = $validated['type'] === 'expense'
                        ? -abs($split['amount'])
                        : abs($split['amount']);

                    SplitTransaction::create([
                        'transaction_id' => $transaction->id,
                        'category_id' => $split['category_id'],
                        'amount' => $splitAmount,
                    ]);
                }
            }
        });

        return redirect()->route('transactions.index');
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        // If it's a transfer, delete the paired transaction too
        if ($transaction->transfer_pair_id) {
            Transaction::where('id', $transaction->transfer_pair_id)->delete();
        }

        // Delete splits (cascades automatically due to foreign key, but being explicit)
        $transaction->splits()->delete();

        $transaction->delete();

        return redirect()->route('transactions.index');
    }

    public function toggleCleared(Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $transaction->update(['cleared' => !$transaction->cleared]);

        return back();
    }
}
