<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\Transaction;
use App\Policies\AccountPolicy;
use App\Policies\CategoryGroupPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register policies
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(CategoryGroup::class, CategoryGroupPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
    }
}
