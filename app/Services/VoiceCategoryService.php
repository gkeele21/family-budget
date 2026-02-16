<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Services\Concerns\CallsClaudeApi;
use Illuminate\Support\Facades\DB;

class VoiceCategoryService
{
    use CallsClaudeApi;

    public function parse(string $transcript, Budget $budget): array
    {
        $context = $this->buildContext($budget);
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

        $groups = $parsed['groups'] ?? [];
        if (empty($groups)) {
            return [
                'status' => 'error',
                'message' => 'No categories could be parsed.',
            ];
        }

        return $this->createGroupsAndCategories($groups, $budget);
    }

    private function buildContext(Budget $budget): array
    {
        $existingGroups = $budget->categoryGroups()
            ->with(['categories' => fn ($q) => $q->orderBy('sort_order')->select(['id', 'group_id', 'name'])])
            ->orderBy('sort_order')
            ->get(['id', 'budget_id', 'name']);

        return ['existingGroups' => $existingGroups];
    }

    private function buildSystemPrompt(array $context): string
    {
        $existingGroupsJson = $context['existingGroups']->map(fn ($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'categories' => $g->categories->pluck('name')->toArray(),
        ])->toJson(JSON_PRETTY_PRINT);

        $emojiList = '🛒 🍎 🥗 🛍️ 🏠 ⚡ 💧 📱 🌐 🚗 ⛽ 🍽️ ☕ 🎬 🎮 🎵 💪 💊 👕 ✂️ 🎁 ✈️ 🏖️ 📚 🛡️ 💳 🎓 🎯 🆘 🐾 👶 🏋️';

        return <<<PROMPT
You are a category parser for an envelope budgeting app. Parse the user's spoken description into category groups and categories.

Rules:
- Parse group names and category names with optional default budget amounts.
- If a spoken group name closely matches an existing group (fuzzy match), use the existing group's ID. Set "existing_group_id" to the matched ID.
- If creating a new group, set "existing_group_id" to null.
- For each category, suggest an appropriate emoji icon from this list: {$emojiList}
- Pick the emoji that best represents the category's purpose (e.g. "Rent" → 🏠, "Groceries" → 🛒, "Electric" → ⚡, "Gas" → ⛽, "Dining" → 🍽️, "Coffee" → ☕, "Gym" → 🏋️).
- Default amounts are optional. If the user says a dollar amount after a category name, use it as default_amount. If not mentioned, set default_amount to null.
- Support multiple groups in one utterance.
- Title-case category names and group names.
- If you cannot understand the input at all, return status "error".

Existing groups in this budget:
{$existingGroupsJson}

Respond with ONLY valid JSON (no markdown, no explanation) in this exact schema:
{
  "status": "success" | "error",
  "groups": [
    {
      "name": "Group Name",
      "existing_group_id": null,
      "categories": [
        {
          "name": "Category Name",
          "icon": "emoji",
          "default_amount": 0.00
        }
      ]
    }
  ],
  "error_message": null
}

If default_amount was not specified for a category, set it to null (not 0).
If status is "error", include error_message explaining why.
PROMPT;
    }

    private function createGroupsAndCategories(array $groups, Budget $budget): array
    {
        $created = [];

        DB::transaction(function () use ($groups, $budget, &$created) {
            foreach ($groups as $groupData) {
                $group = null;

                // Try to use existing group if specified
                if (!empty($groupData['existing_group_id'])) {
                    $group = CategoryGroup::where('id', $groupData['existing_group_id'])
                        ->where('budget_id', $budget->id)
                        ->first();
                }

                // Create new group if not matched
                if (!$group) {
                    $maxOrder = $budget->categoryGroups()->max('sort_order') ?? 0;
                    $group = CategoryGroup::create([
                        'budget_id' => $budget->id,
                        'name' => $groupData['name'],
                        'sort_order' => $maxOrder + 1,
                    ]);
                }

                $groupResult = [
                    'id' => $group->id,
                    'name' => $group->name,
                    'is_new' => empty($groupData['existing_group_id']),
                    'categories' => [],
                ];

                // Create categories
                foreach ($groupData['categories'] ?? [] as $catData) {
                    $maxCatOrder = Category::where('group_id', $group->id)->max('sort_order') ?? 0;

                    $category = Category::create([
                        'group_id' => $group->id,
                        'name' => $catData['name'],
                        'icon' => $catData['icon'] ?? null,
                        'default_amount' => $catData['default_amount'] ?? null,
                        'sort_order' => $maxCatOrder + 1,
                    ]);

                    $groupResult['categories'][] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'icon' => $category->icon,
                        'default_amount' => $category->default_amount ? (float) $category->default_amount : null,
                    ];
                }

                $created[] = $groupResult;
            }
        });

        return [
            'status' => 'created',
            'groups' => $created,
        ];
    }
}
