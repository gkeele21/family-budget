# Transactions

## Overview

Transactions are the core data of the budgeting system. Every expense, income, and transfer is recorded as a transaction, affecting account balances and category spending.

## Key Concepts

- **Expense**: Money going out (negative amount, red)
- **Income**: Money coming in (positive amount, green)
- **Transfer**: Money moving between accounts (neutral, blue)
- **Cleared**: Transaction confirmed with bank statement
- **Split**: Single transaction divided across multiple categories

## Code Locations

### Controllers
- `app/Http/Controllers/TransactionController.php` - CRUD, toggle cleared, filters
- `app/Http/Controllers/VoiceTransactionController.php` - Voice input processing via Claude API

### Services
- `app/Services/VoiceTransactionService.php` - Parses voice transcript into transaction(s)
- `app/Services/Concerns/CallsClaudeApi.php` - Shared trait for Claude API calls

### Pages
- `resources/js/Pages/Transactions/Index.vue` - Transaction list with filters
- `resources/js/Pages/Transactions/Create.vue` - Add new transaction
- `resources/js/Pages/Transactions/Edit.vue` - Edit existing transaction

### Components
- `resources/js/Components/Domain/VoiceOverlay.vue` - Voice input overlay with Budget Guy avatar

### Composables
- `resources/js/Composables/useSpeechRecognition.js` - Browser Web Speech API wrapper

### Models
- `app/Models/Transaction.php` - Main transaction model
- `app/Models/SplitTransaction.php` - For split transactions

## Transaction Types

### Expense
- **Amount**: Negative (stored as -50.00)
- **Display**: Red, shows as "-$50.00"
- **Category**: Required
- **Payee**: Optional but typical
- **Effect**: Reduces account balance, reduces category available

### Income
- **Amount**: Positive (stored as 2000.00)
- **Display**: Green, shows as "+$2,000.00"
- **Category**: Required (often an "Income" category)
- **Payee**: Optional
- **Effect**: Increases account balance, increases category available

### Transfer
- **Amount**: Stored as two transactions (negative in source, positive in destination)
- **Display**: Blue, shows as "$500.00"
- **Category**: None
- **Payee**: None
- **Effect**: Balances cancel out, just moves money between accounts
- **Linked**: `transfer_pair_id` connects the two transactions

## User Workflows

### Add Expense
1. Tap FAB (+) or navigate to Add Transaction
2. Type defaults to "Expense"
3. Enter amount (stored as negative)
4. Select or enter payee
5. Select category (auto-fills if payee has default)
6. Select account
7. Date defaults to today
8. Toggle cleared if already reconciled
9. Add memo (optional)
10. Save

### Add Income
1. Toggle type to "Income"
2. Enter amount (stored as positive)
3. Select payee and category
4. Complete other fields
5. Save

### Add Transfer
1. Toggle type to "Transfer"
2. Form changes: no payee/category fields
3. Enter amount
4. Select "From" account
5. Select "To" account
6. Date and cleared status
7. Save creates two linked transactions

### Edit Transaction
1. Tap transaction in list
2. Edit any field
3. Save updates transaction
4. For transfers, edits update both linked transactions

### Toggle Cleared
1. Tap the cleared indicator (circle) on transaction row
2. Toggles cleared status
3. Toast confirms with Undo option
4. Account cleared/uncleared balances update

### Delete Transaction
1. Swipe left on transaction row
2. Tap delete
3. Confirm deletion
4. For transfers, deletes both linked transactions

### Split Transaction
1. In category field, select "Split Transaction"
2. Add multiple category/amount pairs
3. Bar shows remaining to assign (yellow = unassigned, green = balanced)
4. Must balance to total amount
5. Save creates parent transaction + split records
6. Shows as "3 Categories" in transaction list

## Filters

### Account Filter
- Horizontal scrolling account pills
- "All Accounts" shows everything
- Tap account to filter transactions

### Search
- Search icon in header
- Searches payee name, memo

### Date Range
- Filter by month or date range

### Cleared Status
- Filter by cleared/uncleared

## Payee Defaults

When a payee is selected:
1. Check if payee has `default_category_id`
2. Auto-fill category field
3. Show hint: "Default for [Payee]"

After saving with different category:
1. Prompt: "Update default for [Payee]?"
2. If yes, updates payee's default category

## Voice Input

Budget Guy supports voice-powered transaction creation. Users speak naturally (e.g., "Spent $45 at Target on groceries") and the app creates transactions automatically.

### How It Works (Accumulate & Review Flow)
1. Tap the Budget Guy avatar FAB (positioned beside the main FAB)
2. Full-screen overlay appears with the avatar in listening state
3. Browser Speech Recognition captures the transcript in real-time
4. Transcript is sent to the backend with `preview: true` — Claude API parses it but nothing is created yet
5. Parsed transactions appear in a **review list** where the user can inspect them
6. User can tap **"Add More"** to record additional batches, which append to the review list
7. User can remove individual items from the review list with the X button
8. When satisfied, user taps **"Create All (N)"** to create everything in one batch
9. All transactions share a single `voice_batch_id` for undo

### Voice Overlay States
- **Listening**: Avatar with pulse rings + red mic badge, speech bubble shows live transcript
- **Processing**: Avatar with bob animation + thinking dots bubble
- **Review**: Avatar + scrollable transaction list + "Add More" / "Create All (N)" buttons
- **Creating**: Avatar with bob animation + thinking dots (while batch is being saved)
- **Success**: Avatar with green check badge, shows created transaction(s)
- **Clarification**: Avatar asks follow-up question (e.g., which account?) with inline options
- **Error**: Avatar with red `?` badge, shows transcript. "Back to List" if accumulated items exist, otherwise "Try Again" / "Cancel"

### Voice Trigger
- Avatar FAB on Transactions Index page (side-by-side with main FAB)
- Requires `aiEnabled`, `voiceSupported`, and `voiceInputEnabled` feature flags
- Also available inline on the Create Transaction page

### Backend Endpoints
- `POST /transactions/voice/parse` — accepts `preview: true` to parse without creating (`preview()` method)
- `POST /transactions/voice/clarify` — accepts `preview: true` to resolve clarifications without creating
- `POST /transactions/voice/batch-create` — creates all accumulated transactions under one batch ID
- `DELETE /transactions/voice/undo/{batchId}` — deletes all transactions in a batch

### Batch Creation
Voice input can create multiple transactions across multiple recordings. Each recording is parsed independently by Claude API, and results accumulate in the review list. The "Create All" action creates everything in a single database transaction with a shared `voice_batch_id` for undo.

## Design Decisions

### Why Transfers Create Two Transactions
**Decision**: Each account gets its own transaction record

**Reasoning**:
- Each account's balance is correct from its own transactions
- Can view/edit either side independently
- Standard accounting double-entry approach
- `transfer_pair_id` maintains the link

### Why Amount Sign Conventions
**Decision**: Expense=negative, Income=positive

**Reasoning**:
- Matches mental model (spending reduces, earning increases)
- Simple balance calculation: starting + sum(amounts)
- Clear in UI with red/green colors

## Related Features

- [Accounts](accounts.md) - Transactions belong to accounts
- [Budget View](budget-view.md) - Transactions affect category spending
- [Recurring](recurring.md) - Automated transaction creation
