<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryGroupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Budgets
    Route::get('/budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::post('/budgets/{budget}/select', [BudgetController::class, 'select'])->name('budgets.select');

    // Budget View (monthly budgeting)
    Route::get('/budget/{month?}', [BudgetController::class, 'index'])->name('budget.index');
    Route::put('/budget/{month}', [BudgetController::class, 'update'])->name('budget.update');

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

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/accounts', [SettingsController::class, 'accounts'])->name('settings.accounts');
    Route::get('/settings/categories', [SettingsController::class, 'categories'])->name('settings.categories');

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
