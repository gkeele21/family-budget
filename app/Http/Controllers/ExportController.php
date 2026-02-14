<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        // Get date range from transactions
        $dateRange = [
            'earliest' => $budget->transactions()->min('date'),
            'latest' => $budget->transactions()->max('date'),
        ];

        return Inertia::render('Settings/Export/Index', [
            'budgetName' => $budget->name,
            'dateRange' => $dateRange,
            'transactionCount' => $budget->transactions()->count(),
        ]);
    }

    public function export(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        $validated = $request->validate([
            'format' => 'required|in:csv,json',
            'type' => 'required|in:transactions,budget,all',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $format = $validated['format'];
        $type = $validated['type'];
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        if ($format === 'json') {
            return $this->exportJson($budget, $type, $startDate, $endDate);
        }

        return $this->exportCsv($budget, $type, $startDate, $endDate);
    }

    private function exportJson(Budget $budget, string $type, ?string $startDate, ?string $endDate): StreamedResponse
    {
        $data = $this->gatherExportData($budget, $type, $startDate, $endDate);
        $filename = $this->generateFilename($budget->name, 'json');

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    private function exportCsv(Budget $budget, string $type, ?string $startDate, ?string $endDate): StreamedResponse
    {
        $data = $this->gatherExportData($budget, $type, $startDate, $endDate);
        $filename = $this->generateFilename($budget->name, 'csv');

        return response()->streamDownload(function () use ($data, $type) {
            $output = fopen('php://output', 'w');

            if ($type === 'transactions' || $type === 'all') {
                // Transaction headers
                fputcsv($output, ['--- TRANSACTIONS ---']);
                fputcsv($output, ['Date', 'Account', 'Payee', 'Category', 'Memo', 'Amount', 'Type', 'Cleared']);

                foreach ($data['transactions'] as $t) {
                    fputcsv($output, [
                        $t['date'],
                        $t['account'],
                        $t['payee'],
                        $t['category'],
                        $t['memo'],
                        $t['amount'],
                        $t['type'],
                        $t['cleared'] ? 'Yes' : 'No',
                    ]);
                }
            }

            if ($type === 'budget' || $type === 'all') {
                if ($type === 'all') {
                    fputcsv($output, []); // Empty row separator
                }
                fputcsv($output, ['--- BUDGET ALLOCATIONS ---']);
                fputcsv($output, ['Month', 'Category Group', 'Category', 'Budgeted']);

                foreach ($data['budget'] as $b) {
                    fputcsv($output, [
                        $b['month'],
                        $b['group'],
                        $b['category'],
                        $b['budgeted'],
                    ]);
                }
            }

            if ($type === 'all') {
                fputcsv($output, []);
                fputcsv($output, ['--- ACCOUNTS ---']);
                fputcsv($output, ['Name', 'Type', 'Balance', 'Cleared Balance', 'Is Closed']);

                foreach ($data['accounts'] as $a) {
                    fputcsv($output, [
                        $a['name'],
                        $a['type'],
                        $a['balance'],
                        $a['cleared_balance'],
                        $a['is_closed'] ? 'Yes' : 'No',
                    ]);
                }

                fputcsv($output, []);
                fputcsv($output, ['--- CATEGORIES ---']);
                fputcsv($output, ['Group', 'Category', 'Icon', 'Default Amount', 'Is Hidden']);

                foreach ($data['categories'] as $c) {
                    fputcsv($output, [
                        $c['group'],
                        $c['name'],
                        $c['icon'],
                        $c['default_amount'],
                        $c['is_hidden'] ? 'Yes' : 'No',
                    ]);
                }

                fputcsv($output, []);
                fputcsv($output, ['--- PAYEES ---']);
                fputcsv($output, ['Name', 'Default Category']);

                foreach ($data['payees'] as $p) {
                    fputcsv($output, [
                        $p['name'],
                        $p['default_category'],
                    ]);
                }
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function gatherExportData(Budget $budget, string $type, ?string $startDate, ?string $endDate): array
    {
        $data = [];

        if ($type === 'transactions' || $type === 'all') {
            $query = $budget->transactions()
                ->with(['account', 'category', 'payee', 'splits.category'])
                ->orderBy('date', 'desc');

            if ($startDate) {
                $query->where('date', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('date', '<=', $endDate);
            }

            $data['transactions'] = $query->get()->flatMap(function ($t) {
                if ($t->isSplit()) {
                    // Export each split as a separate line
                    return $t->splits->map(fn($s) => [
                        'date' => $t->date->format('Y-m-d'),
                        'account' => $t->account->name,
                        'payee' => $t->payee?->name ?? ($t->type === 'transfer' ? 'Transfer' : ''),
                        'category' => $s->category?->name ?? '',
                        'memo' => $t->memo,
                        'amount' => (float) $s->amount,
                        'type' => $t->type,
                        'cleared' => $t->cleared,
                    ]);
                }

                return [[
                    'date' => $t->date->format('Y-m-d'),
                    'account' => $t->account->name,
                    'payee' => $t->payee?->name ?? ($t->type === 'transfer' ? 'Transfer' : ''),
                    'category' => $t->category?->name ?? '',
                    'memo' => $t->memo,
                    'amount' => (float) $t->amount,
                    'type' => $t->type,
                    'cleared' => $t->cleared,
                ]];
            })->toArray();
        }

        if ($type === 'budget' || $type === 'all') {
            $data['budget'] = $budget->budgetAllocations()
                ->with(['category.categoryGroup'])
                ->orderBy('month')
                ->get()
                ->map(fn($b) => [
                    'month' => $b->month,
                    'group' => $b->category->categoryGroup->name,
                    'category' => $b->category->name,
                    'budgeted' => (float) $b->budgeted,
                ])->toArray();
        }

        if ($type === 'all') {
            $data['accounts'] = $budget->accounts()
                ->orderBy('sort_order')
                ->get()
                ->map(fn($a) => [
                    'name' => $a->name,
                    'type' => $a->type,
                    'balance' => (float) $a->balance,
                    'cleared_balance' => (float) $a->cleared_balance,
                    'is_closed' => $a->is_closed,
                ])->toArray();

            $data['categories'] = $budget->categoryGroups()
                ->with('categories')
                ->orderBy('sort_order')
                ->get()
                ->flatMap(fn($g) => $g->categories->map(fn($c) => [
                    'group' => $g->name,
                    'name' => $c->name,
                    'icon' => $c->icon,
                    'default_amount' => (float) $c->default_amount,
                    'is_hidden' => $c->is_hidden,
                ]))->toArray();

            $data['payees'] = $budget->payees()
                ->with('defaultCategory')
                ->orderBy('name')
                ->get()
                ->map(fn($p) => [
                    'name' => $p->name,
                    'default_category' => $p->defaultCategory?->name ?? '',
                ])->toArray();
        }

        return $data;
    }

    private function generateFilename(string $budgetName, string $extension): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $budgetName);
        $date = now()->format('Y-m-d');
        return "{$safeName}_export_{$date}.{$extension}";
    }
}
