<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryGroupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PayeeController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\SharingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\VoiceCategoryController;
use App\Http\Controllers\VoiceTransactionController;
use Illuminate\Support\Facades\Route;

// Welcome page for guests
Route::get('/', [OnboardingController::class, 'welcome'])->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    // Onboarding
    Route::get('/setup', [OnboardingController::class, 'setup'])->name('onboarding.setup');
    Route::post('/setup', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('/setup/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Budgets
    Route::get('/budgets/edit', [BudgetController::class, 'edit'])->name('budgets.edit');
    Route::put('/budgets/edit', [BudgetController::class, 'updateBudget'])->name('budgets.update-budget');
    Route::post('/budgets/{budget}/select', [BudgetController::class, 'select'])->name('budgets.select');

    // Plan (projections)
    Route::get('/plan', [PlanController::class, 'index'])->name('plan.index');

    // Budget View (monthly budgeting)
    Route::get('/budget/{month?}', [BudgetController::class, 'index'])->name('budget.index');
    Route::put('/budget/{month}', [BudgetController::class, 'update'])->name('budget.update');
    Route::post('/budget/{month}/copy-last-month', [BudgetController::class, 'copyLastMonth'])->name('budget.copy-last-month');
    Route::post('/budget/{month}/move-money', [BudgetController::class, 'moveMoney'])->name('budget.move-money');
    Route::post('/budget/{month}/apply-defaults', [BudgetController::class, 'applyDefaults'])->name('budget.apply-defaults');
    Route::post('/budget/{month}/apply-projections', [BudgetController::class, 'applyProjections'])->name('budget.apply-projections');
    Route::post('/budget/{month}/clear', [BudgetController::class, 'clearBudget'])->name('budget.clear');
    Route::post('/budget/projections', [BudgetController::class, 'saveProjections'])->name('budget.save-projections');
    Route::post('/budget/projections/clear', [BudgetController::class, 'clearProjections'])->name('budget.clear-projections');
    Route::post('/budget/projections/apply-to-defaults', [BudgetController::class, 'applyProjectionsToDefaults'])->name('budget.apply-projections-to-defaults');
    Route::get('/budget/{month}/category/{category}', [BudgetController::class, 'categoryDetail'])->name('budget.category-detail');

    // Reorder endpoints (must be before resource routes)
    Route::post('/accounts/reorder', [AccountController::class, 'reorder'])->name('accounts.reorder');
    Route::post('/category-groups/reorder', [CategoryGroupController::class, 'reorder'])->name('category-groups.reorder');
    Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');

    // Accounts
    Route::resource('accounts', AccountController::class)->except(['show']);

    // Category Groups & Categories
    Route::resource('category-groups', CategoryGroupController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['index', 'show']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::post('/transactions/{transaction}/toggle-cleared', [TransactionController::class, 'toggleCleared'])->name('transactions.toggle-cleared');

    // Voice Transactions (JSON API)
    Route::post('/transactions/voice/parse', [VoiceTransactionController::class, 'parse'])->name('transactions.voice.parse');
    Route::post('/transactions/voice/clarify', [VoiceTransactionController::class, 'clarify'])->name('transactions.voice.clarify');
    Route::delete('/transactions/voice/undo/{batchId}', [VoiceTransactionController::class, 'undoBatch'])->name('transactions.voice.undo');

    // Voice Categories (JSON API)
    Route::post('/categories/voice/parse', [VoiceCategoryController::class, 'parse'])->name('categories.voice.parse');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/accounts', [SettingsController::class, 'accounts'])->name('settings.accounts');
    Route::get('/settings/categories', [SettingsController::class, 'categories'])->name('settings.categories');

    // Payee Management
    Route::get('/settings/payees', [PayeeController::class, 'index'])->name('payees.index');
    Route::put('/settings/payees/{payee}', [PayeeController::class, 'update'])->name('payees.update');
    Route::delete('/settings/payees/{payee}', [PayeeController::class, 'destroy'])->name('payees.destroy');

    // Export
    Route::get('/settings/export', [ExportController::class, 'index'])->name('export.index');
    Route::get('/settings/export/download', [ExportController::class, 'export'])->name('export.download');

    // Recurring Transactions
    Route::get('/settings/recurring', [RecurringTransactionController::class, 'index'])->name('recurring.index');
    Route::get('/settings/recurring/create', [RecurringTransactionController::class, 'create'])->name('recurring.create');
    Route::post('/settings/recurring', [RecurringTransactionController::class, 'store'])->name('recurring.store');
    Route::get('/settings/recurring/{recurring}/edit', [RecurringTransactionController::class, 'edit'])->name('recurring.edit');
    Route::put('/settings/recurring/{recurring}', [RecurringTransactionController::class, 'update'])->name('recurring.update');
    Route::delete('/settings/recurring/{recurring}', [RecurringTransactionController::class, 'destroy'])->name('recurring.destroy');
    Route::post('/settings/recurring/{recurring}/toggle', [RecurringTransactionController::class, 'toggleActive'])->name('recurring.toggle');

    // Sharing / Multi-User
    Route::get('/settings/sharing', [SharingController::class, 'index'])->name('sharing.index');
    Route::post('/settings/sharing/invite', [SharingController::class, 'invite'])->name('sharing.invite');
    Route::delete('/settings/sharing/invite/{invite}', [SharingController::class, 'cancelInvite'])->name('sharing.cancel-invite');
    Route::delete('/settings/sharing/member/{user}', [SharingController::class, 'removeMember'])->name('sharing.remove-member');
    Route::get('/settings/sharing/pending', [SharingController::class, 'pendingInvites'])->name('sharing.pending');
    Route::post('/settings/sharing/accept', [SharingController::class, 'acceptInvite'])->name('sharing.accept-invite');
    Route::post('/settings/sharing/decline', [SharingController::class, 'declineInvite'])->name('sharing.decline-invite');

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
