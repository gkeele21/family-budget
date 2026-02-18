<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Payee;
use App\Models\SplitTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Concerns\CallsClaudeApi;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoiceTransactionService
{
    use CallsClaudeApi;
    /**
     * Parse a voice transcript and create transactions if fully resolved.
     */
    public function parse(string $transcript, Budget $budget, User $user): array
    {
        $context = $this->buildBudgetContext($budget);
        $systemPrompt = $this->buildSystemPrompt($context);

        $claudeResponse = $this->callClaudeApi($systemPrompt, $transcript);

        if ($claudeResponse === null) {
            return [
                'status' => 'error',
                'message' => $this->getApiErrorMessage(),
            ];
        }

        $parsed = $this->parseClaudeResponse($claudeResponse);

        if ($parsed === null) {
            return [
                'status' => 'error',
                'message' => "Couldn't understand that. Try speaking more clearly.",
            ];
        }

        if ($parsed['status'] === 'error') {
            return [
                'status' => 'error',
                'message' => $parsed['error_message'] ?? "Couldn't understand that.",
            ];
        }

        if ($parsed['status'] === 'clarification_needed') {
            $sessionContext = Crypt::encryptString(json_encode([
                'transactions' => $parsed['transactions'],
                'clarifications' => $parsed['clarifications'],
                'transcript' => $transcript,
            ]));

            return [
                'status' => 'clarification_needed',
                'clarifications' => $parsed['clarifications'],
                'session_context' => $sessionContext,
            ];
        }

        // Validate all transactions have required fields
        $validated = $this->validateParsedTransactions($parsed['transactions'], $budget);
        if ($validated['has_errors']) {
            return [
                'status' => 'error',
                'message' => $validated['message'],
            ];
        }

        $result = $this->createTransactions($validated['transactions'], $budget, $user);

        return [
            'status' => 'created',
            'transactions' => $result['transactions'],
            'batch_id' => $result['batch_id'],
        ];
    }

    /**
     * Resolve clarifications and create transactions.
     */
    public function clarify(string $encryptedContext, array $answers, Budget $budget, User $user): array
    {
        $context = json_decode(Crypt::decryptString($encryptedContext), true);

        if (!$context) {
            return [
                'status' => 'error',
                'message' => 'Invalid session. Please try again.',
            ];
        }

        $transactions = $context['transactions'];

        // Apply answers to the parsed transactions
        foreach ($answers as $answer) {
            $index = $answer['transaction_index'];
            $field = $answer['field'];
            $value = $answer['value'];

            if (isset($transactions[$index])) {
                $transactions[$index][$field] = $value;
            }
        }

        $validated = $this->validateParsedTransactions($transactions, $budget);
        if ($validated['has_errors']) {
            return [
                'status' => 'error',
                'message' => $validated['message'],
            ];
        }

        $result = $this->createTransactions($validated['transactions'], $budget, $user);

        return [
            'status' => 'created',
            'transactions' => $result['transactions'],
            'batch_id' => $result['batch_id'],
        ];
    }

    /**
     * Parse a voice transcript and return previewed transactions without creating.
     */
    public function preview(string $transcript, Budget $budget, User $user): array
    {
        $context = $this->buildBudgetContext($budget);
        $systemPrompt = $this->buildSystemPrompt($context);

        $claudeResponse = $this->callClaudeApi($systemPrompt, $transcript);

        if ($claudeResponse === null) {
            return [
                'status' => 'error',
                'message' => $this->getApiErrorMessage(),
            ];
        }

        $parsed = $this->parseClaudeResponse($claudeResponse);

        if ($parsed === null) {
            return [
                'status' => 'error',
                'message' => "Couldn't understand that. Try speaking more clearly.",
            ];
        }

        if ($parsed['status'] === 'error') {
            return [
                'status' => 'error',
                'message' => $parsed['error_message'] ?? "Couldn't understand that.",
            ];
        }

        if ($parsed['status'] === 'clarification_needed') {
            $sessionContext = Crypt::encryptString(json_encode([
                'transactions' => $parsed['transactions'],
                'clarifications' => $parsed['clarifications'],
                'transcript' => $transcript,
            ]));

            return [
                'status' => 'clarification_needed',
                'clarifications' => $parsed['clarifications'],
                'session_context' => $sessionContext,
            ];
        }

        $validated = $this->validateParsedTransactions($parsed['transactions'], $budget);
        if ($validated['has_errors']) {
            return [
                'status' => 'error',
                'message' => $validated['message'],
            ];
        }

        return [
            'status' => 'previewed',
            'transactions' => $this->enrichForDisplay($validated['transactions'], $budget),
        ];
    }

    /**
     * Resolve clarifications and return previewed transactions without creating.
     */
    public function previewClarify(string $encryptedContext, array $answers, Budget $budget, User $user): array
    {
        $context = json_decode(Crypt::decryptString($encryptedContext), true);

        if (!$context) {
            return [
                'status' => 'error',
                'message' => 'Invalid session. Please try again.',
            ];
        }

        $transactions = $context['transactions'];

        foreach ($answers as $answer) {
            $index = $answer['transaction_index'];
            $field = $answer['field'];
            $value = $answer['value'];

            if (isset($transactions[$index])) {
                $transactions[$index][$field] = $value;
            }
        }

        $validated = $this->validateParsedTransactions($transactions, $budget);
        if ($validated['has_errors']) {
            return [
                'status' => 'error',
                'message' => $validated['message'],
            ];
        }

        return [
            'status' => 'previewed',
            'transactions' => $this->enrichForDisplay($validated['transactions'], $budget),
        ];
    }

    /**
     * Create transactions from pre-validated data in a single batch.
     */
    public function batchCreate(array $transactionDataArray, Budget $budget, User $user): array
    {
        $validated = $this->validateParsedTransactions($transactionDataArray, $budget);
        if ($validated['has_errors']) {
            return [
                'status' => 'error',
                'message' => $validated['message'],
            ];
        }

        $result = $this->createTransactions($validated['transactions'], $budget, $user);

        return [
            'status' => 'created',
            'transactions' => $result['transactions'],
            'batch_id' => $result['batch_id'],
        ];
    }

    /**
     * Delete all transactions in a voice batch.
     */
    public function undoBatch(string $batchId, Budget $budget): array
    {
        $count = Transaction::where('voice_batch_id', $batchId)
            ->where('budget_id', $budget->id)
            ->delete();

        return ['status' => 'undone', 'count' => $count];
    }

    /**
     * Load budget context for the Claude prompt.
     */
    private function buildBudgetContext(Budget $budget): array
    {
        $accounts = $budget->accounts()
            ->where('is_closed', false)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type']);

        $categoryGroups = $budget->categoryGroups()
            ->with(['categories' => fn ($q) => $q->where('is_hidden', false)->orderBy('sort_order')->select(['id', 'group_id', 'name'])])
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $payees = $budget->payees()
            ->orderBy('name')
            ->get(['id', 'name', 'default_category_id']);

        return compact('accounts', 'categoryGroups', 'payees');
    }

    /**
     * Build the system prompt for Claude.
     */
    private function buildSystemPrompt(array $context): string
    {
        $accountsJson = $context['accounts']->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'type' => $a->type,
        ])->toJson();

        $categoriesJson = $context['categoryGroups']->map(fn ($g) => [
            'group' => $g->name,
            'categories' => $g->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->toArray(),
        ])->toJson();

        $payeesJson = $context['payees']->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'default_category_id' => $p->default_category_id,
        ])->toJson();

        $today = now()->toDateString();
        $accountCount = $context['accounts']->count();

        return <<<PROMPT
You are a transaction parser for an envelope budgeting app. Parse the user's spoken transaction description into structured JSON.

Rules:
- Support three transaction types: "expense", "income", and "transfer".
- Default type is "expense" unless the user explicitly says income words like "got paid", "received", "earned", "paycheck", "income", "deposit", or transfer words like "transferred", "moved", "deposited cash".
- Default date is today: {$today}. If the user says "yesterday", use the previous day. Parse specific dates like "Feb 12" or "January 3rd" relative to the current year. Apply a spoken date to ALL transactions in the utterance unless different dates are specified.
- Amount must always be a positive number. The system handles the sign based on type.
- Match payee names to existing payees when clearly the same (fuzzy match). Use the spoken name if no close match. Transfers have no payee.
- Match category names to existing categories when clearly the same (fuzzy match). If no match but the matched payee has a default_category_id, use that default. If still no match, set category_id to null (it will be "Unassigned") — do NOT flag for clarification. Transfers have no category.
- Match account names to existing accounts when mentioned. If no account is mentioned and there is exactly {$accountCount} account(s), use the first one. If multiple accounts exist and none is mentioned, set account_id to null and flag for clarification.
- For transfers: account_id is the source ("from") account, to_account_id is the destination ("to") account. Both must be resolved.
- Split transactions: When the user specifies multiple categories for a single transaction (e.g. "$250 at Costco, $200 groceries and $50 household"), use the "splits" array. Each split has a category_id and amount. The split amounts must sum to the total transaction amount. Do NOT use splits for separate transactions at different payees.
- Support multiple transactions in one utterance separated by "and" or similar.
- Do NOT parse budget adjustments or category management requests.
- If you cannot understand the input at all, return status "error".

Today's date: {$today}

Accounts:
{$accountsJson}

Categories:
{$categoriesJson}

Payees:
{$payeesJson}

Respond with ONLY valid JSON (no markdown, no explanation) in this exact schema:
{
  "status": "success" | "clarification_needed" | "error",
  "transactions": [
    {
      "type": "expense" | "income" | "transfer",
      "amount": 0.00,
      "payee_name": "string or null",
      "category_id": null,
      "account_id": null,
      "to_account_id": null,
      "date": "YYYY-MM-DD",
      "memo": null,
      "splits": null
    }
  ],
  "clarifications": [
    {
      "transaction_index": 0,
      "field": "account_id" | "category_id" | "to_account_id",
      "message": "Which account for \$X at Payee?",
      "options": [{"id": 0, "name": "string"}]
    }
  ],
  "error_message": null
}

Transaction field rules:
- For expense/income: payee_name required, category_id required (or use splits), to_account_id must be null.
- For transfer: payee_name must be null, category_id must be null, splits must be null, to_account_id required (different from account_id).
- splits format (when used instead of category_id): [{"category_id": 1, "amount": 50.00}, ...]. Set category_id to null when using splits.

If status is "success", all transaction fields must be fully resolved (no nulls for account_id). category_id may be null (meaning "Unassigned") if no category was spoken and the payee has no default — this is fine, do not flag for clarification.
If status is "clarification_needed", include partial transactions and the clarifications array describing what needs user input.
If status is "error", include error_message explaining why.
PROMPT;
    }

    /**
     * Validate parsed transactions against the budget's actual data.
     */
    private function validateParsedTransactions(array $transactions, Budget $budget): array
    {
        $validAccountIds = $budget->accounts()->where('is_closed', false)->pluck('id')->toArray();
        $validCategoryIds = $budget->categoryGroups()
            ->with(['categories' => fn ($q) => $q->where('is_hidden', false)])
            ->get()
            ->flatMap(fn ($g) => $g->categories->pluck('id'))
            ->toArray();

        $validated = [];

        foreach ($transactions as $tx) {
            $type = $tx['type'] ?? '';
            if (!in_array($type, ['expense', 'income', 'transfer'])) {
                return ['has_errors' => true, 'message' => 'Invalid transaction type detected.'];
            }

            $amount = floatval($tx['amount'] ?? 0);
            if ($amount <= 0) {
                return ['has_errors' => true, 'message' => 'Invalid amount detected.'];
            }

            // Account must exist in budget
            if (!$tx['account_id'] || !in_array($tx['account_id'], $validAccountIds)) {
                return ['has_errors' => true, 'message' => 'Could not determine the account.'];
            }

            $entry = [
                'type' => $type,
                'amount' => $amount,
                'payee_name' => $tx['payee_name'] ?? null,
                'account_id' => $tx['account_id'],
                'date' => $tx['date'] ?? now()->toDateString(),
                'memo' => $tx['memo'] ?? null,
            ];

            if ($type === 'transfer') {
                // Transfers need a valid to_account_id
                $toAccountId = $tx['to_account_id'] ?? null;
                if (!$toAccountId || !in_array($toAccountId, $validAccountIds)) {
                    return ['has_errors' => true, 'message' => 'Could not determine the destination account.'];
                }
                if ($toAccountId == $tx['account_id']) {
                    return ['has_errors' => true, 'message' => 'Source and destination accounts must be different.'];
                }
                $entry['to_account_id'] = $toAccountId;
                $entry['category_id'] = null;
                $entry['splits'] = null;
            } else {
                // Check for splits
                $splits = $tx['splits'] ?? null;
                if (is_array($splits) && count($splits) > 1) {
                    $validatedSplits = [];
                    foreach ($splits as $split) {
                        $splitCatId = $split['category_id'] ?? null;
                        $splitAmount = floatval($split['amount'] ?? 0);
                        if ($splitAmount <= 0) continue;
                        if ($splitCatId && !in_array($splitCatId, $validCategoryIds)) {
                            $splitCatId = null;
                        }
                        $validatedSplits[] = [
                            'category_id' => $splitCatId,
                            'amount' => $splitAmount,
                        ];
                    }
                    if (count($validatedSplits) > 1) {
                        $entry['category_id'] = null;
                        $entry['splits'] = $validatedSplits;
                    } else {
                        // Only one valid split — treat as regular
                        $entry['category_id'] = $validatedSplits[0]['category_id'] ?? null;
                        $entry['splits'] = null;
                    }
                } else {
                    $categoryId = $tx['category_id'] ?? null;
                    if ($categoryId && !in_array($categoryId, $validCategoryIds)) {
                        $categoryId = null;
                    }
                    $entry['category_id'] = $categoryId;
                    $entry['splits'] = null;
                }
            }

            $validated[] = $entry;
        }

        if (empty($validated)) {
            return ['has_errors' => true, 'message' => 'No transactions could be parsed.'];
        }

        return ['has_errors' => false, 'transactions' => $validated, 'message' => null];
    }

    /**
     * Enrich validated transactions with display-friendly names for the frontend.
     */
    private function enrichForDisplay(array $transactions, Budget $budget): array
    {
        $accounts = $budget->accounts()->pluck('name', 'id')->toArray();
        $categories = $budget->categoryGroups()
            ->with('categories')
            ->get()
            ->flatMap(fn ($g) => $g->categories->pluck('name', 'id'))
            ->toArray();

        return array_map(function ($tx) use ($accounts, $categories) {
            return [
                'data' => $tx,
                'display' => [
                    'type' => $tx['type'],
                    'amount' => $tx['amount'],
                    'payee_name' => $tx['payee_name'] ?? null,
                    'account_name' => $accounts[$tx['account_id']] ?? 'Unknown',
                    'category_name' => $tx['type'] === 'transfer'
                        ? null
                        : (!empty($tx['splits']) ? null : ($categories[$tx['category_id'] ?? 0] ?? 'Unassigned')),
                    'splits' => !empty($tx['splits']) ? array_map(fn ($s) => [
                        'category' => $categories[$s['category_id'] ?? 0] ?? 'Unassigned',
                        'amount' => $s['amount'],
                    ], $tx['splits']) : null,
                    'to_account_name' => isset($tx['to_account_id']) ? ($accounts[$tx['to_account_id']] ?? null) : null,
                    'date' => $tx['date'],
                ],
            ];
        }, $transactions);
    }

    /**
     * Create the actual transactions in the database.
     */
    private function createTransactions(array $transactions, Budget $budget, User $user): array
    {
        $batchId = (string) Str::uuid();
        $created = [];

        DB::transaction(function () use ($transactions, $budget, $user, $batchId, &$created) {
            foreach ($transactions as $tx) {
                if ($tx['type'] === 'transfer') {
                    $this->createTransfer($tx, $budget, $user, $batchId, $created);
                } else {
                    $this->createExpenseOrIncome($tx, $budget, $user, $batchId, $created);
                }
            }
        });

        return ['transactions' => $created, 'batch_id' => $batchId];
    }

    private function createExpenseOrIncome(array $tx, Budget $budget, User $user, string $batchId, array &$created): void
    {
        // Resolve payee
        $payeeId = null;
        $categoryId = $tx['category_id'];
        if ($tx['payee_name']) {
            $payee = Payee::firstOrCreate(
                ['budget_id' => $budget->id, 'name' => $tx['payee_name']],
                ['default_category_id' => $tx['category_id']]
            );
            $payeeId = $payee->id;

            // Fallback to payee's default category if none was specified
            if (!$categoryId && empty($tx['splits']) && $payee->default_category_id) {
                $categoryId = $payee->default_category_id;
            }
        }

        $amount = $tx['type'] === 'expense' ? -abs($tx['amount']) : abs($tx['amount']);

        $account = Account::find($tx['account_id']);
        $cleared = $account && $account->type === 'cash';

        $transaction = Transaction::create([
            'budget_id' => $budget->id,
            'account_id' => $tx['account_id'],
            'category_id' => $categoryId,
            'payee_id' => $payeeId,
            'amount' => $amount,
            'type' => $tx['type'],
            'date' => $tx['date'],
            'cleared' => $cleared,
            'memo' => $tx['memo'],
            'created_by' => $user->id,
            'voice_batch_id' => $batchId,
        ]);

        // Create split transactions if present
        if (!empty($tx['splits'])) {
            foreach ($tx['splits'] as $split) {
                $splitAmount = $tx['type'] === 'expense'
                    ? -abs($split['amount'])
                    : abs($split['amount']);

                SplitTransaction::create([
                    'transaction_id' => $transaction->id,
                    'category_id' => $split['category_id'],
                    'amount' => $splitAmount,
                ]);
            }
        }

        $created[] = [
            'id' => $transaction->id,
            'payee' => $tx['payee_name'],
            'amount' => $amount,
            'type' => $tx['type'],
            'category' => !empty($tx['splits']) ? 'Split' : ($transaction->category?->name ?? 'Unassigned'),
            'account' => $account?->name,
            'date' => $tx['date'],
        ];
    }

    private function createTransfer(array $tx, Budget $budget, User $user, string $batchId, array &$created): void
    {
        $fromAccount = Account::find($tx['account_id']);
        $toAccount = Account::find($tx['to_account_id']);

        $fromCleared = $fromAccount && $fromAccount->type === 'cash';
        $toCleared = $toAccount && $toAccount->type === 'cash';

        $fromTransaction = Transaction::create([
            'budget_id' => $budget->id,
            'account_id' => $tx['account_id'],
            'category_id' => null,
            'payee_id' => null,
            'amount' => -abs($tx['amount']),
            'type' => 'transfer',
            'date' => $tx['date'],
            'cleared' => $fromCleared,
            'memo' => $tx['memo'],
            'created_by' => $user->id,
            'voice_batch_id' => $batchId,
        ]);

        $toTransaction = Transaction::create([
            'budget_id' => $budget->id,
            'account_id' => $tx['to_account_id'],
            'category_id' => null,
            'payee_id' => null,
            'amount' => abs($tx['amount']),
            'type' => 'transfer',
            'date' => $tx['date'],
            'cleared' => $toCleared,
            'memo' => $tx['memo'],
            'transfer_pair_id' => $fromTransaction->id,
            'created_by' => $user->id,
            'voice_batch_id' => $batchId,
        ]);

        $fromTransaction->update(['transfer_pair_id' => $toTransaction->id]);

        $created[] = [
            'id' => $fromTransaction->id,
            'payee' => "Transfer to {$toAccount->name}",
            'amount' => -abs($tx['amount']),
            'type' => 'transfer',
            'category' => null,
            'account' => $fromAccount?->name,
            'date' => $tx['date'],
        ];
    }
}
