<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TutorialBudgetSeeder extends Seeder
{
    /**
     * Create a tutorial budget for the given user.
     */
    public function run(User $user): Budget
    {
        return DB::transaction(function () use ($user) {
            // Create tutorial budget
            $budget = Budget::create([
                'name' => 'Tutorial Budget',
                'owner_id' => $user->id,
                'is_tutorial' => true,
                'start_month' => now()->format('Y-m'),
            ]);

            // Add user as owner
            $budget->users()->attach($user->id, [
                'role' => 'owner',
                'accepted_at' => now(),
            ]);

            // Create account
            Account::create([
                'budget_id' => $budget->id,
                'name' => 'Sample Checking',
                'type' => 'bank',
                'starting_balance' => 4200.00,
                'sort_order' => 0,
            ]);

            // Create category groups and categories
            $this->createCategories($budget);

            return $budget;
        });
    }

    private function createCategories(Budget $budget): void
    {
        $groupsData = [
            'Bills' => [
                ['name' => 'Rent', 'icon' => '🏠'],
                ['name' => 'Electric', 'icon' => '⚡'],
                ['name' => 'Internet', 'icon' => '🌐'],
            ],
            'Everyday' => [
                ['name' => 'Groceries', 'icon' => '🛒'],
                ['name' => 'Gas', 'icon' => '⛽'],
                ['name' => 'Eating Out', 'icon' => '🍽️'],
            ],
            'Fun' => [
                ['name' => 'Entertainment', 'icon' => '🎬'],
                ['name' => 'Shopping', 'icon' => '🛍️'],
            ],
            'Savings' => [
                ['name' => 'Emergency Fund', 'icon' => '🛟'],
                ['name' => 'Vacation', 'icon' => '✈️'],
            ],
        ];

        $groupOrder = 0;

        foreach ($groupsData as $groupName => $categories) {
            $group = CategoryGroup::create([
                'budget_id' => $budget->id,
                'name' => $groupName,
                'sort_order' => $groupOrder++,
            ]);

            $categoryOrder = 0;
            foreach ($categories as $catData) {
                Category::create([
                    'group_id' => $group->id,
                    'name' => $catData['name'],
                    'icon' => $catData['icon'],
                    'sort_order' => $categoryOrder++,
                ]);
            }
        }
    }
}
