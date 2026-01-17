# Budget App — Build Guide

A YNAB-inspired envelope budgeting app for personal/shared budget management.

---

## Tech Stack

- **Frontend:** Vue 3 (Composition API) + Vite
- **Backend:** Laravel 12 (API)
- **Database:** MySQL
- **Mobile:** Capacitor (wraps Vue app for iOS)
- **AI:** Claude API (Haiku) for voice parsing
- **Voice:** Browser SpeechRecognition API

---

## Design Tokens

### Colors
```css
--color-primary: #76cd26;        /* Bright green - buttons, accents, FAB */
--color-primary-light: #8bd93e;  /* Gradient end */
--color-primary-bg: #edfce0;     /* Light green tint for highlights */
--color-header: #2c2c2e;         /* Dark charcoal - headers */
--color-expense: #c0392b;        /* Red - expenses, overspent */
--color-income: #76cd26;         /* Green - income */
--color-transfer: #888888;       /* Gray - transfers */
--color-text: #333333;
--color-text-secondary: #888888;
--color-background: #f5f5f5;
--color-card: #ffffff;
```

### Typography
```css
--font-primary: 'IBM Plex Sans', sans-serif;
--font-mono: 'IBM Plex Mono', monospace;  /* For amounts */
```

### Spacing
- Mobile width: 100% (max ~428px on larger phones)
- Card border-radius: 12px
- Button border-radius: 12px
- Standard padding: 12-16px

---

## Data Model

### Users
```
id
email
password
name (optional)
created_at
```

### Budgets
```
id
name
owner_id (user who created it)
default_monthly_income (decimal, nullable)
created_at
```

### Budget_Users (sharing)
```
budget_id
user_id
role: 'owner' | 'member'
invited_at
accepted_at
```

### Accounts
```
id
budget_id
name
type: 'checking' | 'savings' | 'credit_card' | 'cash'
balance (calculated from transactions)
starting_balance
sort_order
is_closed: boolean
created_at
```

### Category_Groups
```
id
budget_id
name
sort_order
```

### Categories
```
id
group_id
name
icon (emoji)
default_amount (decimal, nullable)
projections (JSON, e.g., { "1": 400, "2": 425, "3": 450 })
sort_order
is_hidden: boolean
```

### Monthly_Budgets
```
id
category_id
month (YYYY-MM)
budgeted_amount
```

### Transactions
```
id
budget_id
account_id
category_id (nullable for transfers)
payee_id
amount (positive = inflow, negative = outflow)
type: 'expense' | 'income' | 'transfer'
date
cleared: boolean
memo (optional)
transfer_pair_id (links two sides of a transfer)
recurring_id (if generated from recurring)
created_by (user_id)
created_at
```

### Split_Transactions
```
id
transaction_id
category_id
amount
```

### Payees
```
id
budget_id
name
default_category_id (nullable)
```

### Recurring_Transactions
```
id
budget_id
account_id
category_id
payee_id
amount
type: 'expense' | 'income'
frequency: 'daily' | 'weekly' | 'biweekly' | 'monthly' | 'yearly'
next_date
end_date (nullable)
end_after_count (nullable)
is_active: boolean
```

### Invites
```
id
budget_id
email
invited_by (user_id)
token
created_at
accepted_at (nullable)
```

---

## Screens & Features

Reference: `wireframes.html` for visual designs

### 1. Dashboard (Screen 1)
- Shows all accounts grouped by type (Cash, Credit Cards)
- "Available to Budget" summary card (green gradient, charcoal label, white amount)
- Tap account → view transactions for that account
- FAB (+) → add transaction
- Bottom nav: Accounts | Budget | Transactions
- Settings gear icon in header

### 2. Add Transaction (Screen 2)
**Three-way toggle: Expense | Income | Transfer**

**Expense/Income mode:**
- Amount (color matches type)
- Payee (with auto-complete)
- Category (auto-fills from payee default)
- Account
- Date (defaults to today)
- Cleared toggle
- Memo (optional)
- Voice input button

**Transfer mode (Screen 2B):**
- Amount (gray)
- From Account
- To Account
- Date
- Cleared toggle
- Memo (optional)
- Creates linked transactions in both accounts
- No payee or category

### 3. Budget View (Screen 3)
- Month selector (arrows to navigate)
- Summary stats: To Budget | Budgeted | Spent
- Category groups with categories
- Each category shows: name, icon, available amount, progress bar
- Red highlighting for overspent categories
- Tap category → detail view
- "Copy Last Month" quick action (Screen 6A-6C)

### 4. Category Detail (Screen 4)
- Header: category name, budgeted/spent/available
- List of transactions in this category
- Green dot = cleared, empty dot = uncleared
- Tap transaction → edit

### 5. Split Transaction (Screen 5A-5B)
- Select "Split Transaction" in category field
- Add multiple category splits with amounts
- Yellow bar shows unassigned remainder
- Green bar when balanced ($0 remaining)
- Can't save until fully assigned
- Shows "3 Categories" in category field when complete

### 6. Budget Entry (Screen 6A-6F)

**Quick Fill Logic:**
- **If projections exist** → "Apply Projections" button
  - If multiple projections (1, 2, 3), show picker to choose which one
  - Applies selected projection amounts to the budget
- **If no projections** → "Copy Last Month" button
  - Copies last month's actual budgeted amounts

- If overwriting existing amounts, shows warning modal
- If all $0, applies instantly (no popup)
- Green border on edited amounts
- "Ready to Assign" updates in real-time

**Projection Mode (Screen 6D):**
- Toggle into projection mode to plan future months
- **Sticky header** (stays visible on scroll):
  - Expected Income (editable, pre-fills from default)
  - Total Projected | Left to Allocate
- Each category shows: Default | Projected amount
- Edit projected amounts freely
- Shows difference from default (↑ $35)
- "Clear All Projections" resets to defaults
- "Apply Projections" makes projections the actual budget
- Projections persist until changed (don't auto-clear)
- Supports 1-3 projection columns (stored as JSON)

**Move Money Between Categories (Screen 6E-6F):**
- Tap an overspent (red) amount to open modal
- Modal shows "Groceries is over by $50"
- Lists ALL categories with surplus, sorted highest to lowest
- Column headers: Category | Available
- Tap one → moves funds (full amount or whatever's available)
- If partial coverage: modal stays open with remaining amount
- Shows "Moved $28 from Gas" and "still needs $22"
- Category with $0 remaining disappears from list
- Tap "Done" to close when finished

### 7. Transactions List (Screen 7)
**Toggle: All | Recurring**

**All Transactions (Screen 7, 7A):**
- Filter bar: All Accounts, Checking, Visa, Savings (horizontal scroll)
- Search icon in header
- Grouped by date
- Each row: payee, account + category, amount, cleared indicator
- Amount colors: red (expense), green (income), gray (transfer)
- ↻ badge on transactions from recurring
- Tap transaction → edit
- Tap ○/● dot → toggle cleared (with toast + undo)
- Swipe left → delete
- FAB for quick add

**Recurring List (Screen 7B):**
- Grouped by frequency (Monthly, Bi-Weekly, Yearly)
- Shows: payee, account + category, amount, next date
- ~ prefix for variable amounts
- Tap → edit recurring details
- FAB → add new recurring

**Add/Edit Recurring (Screen 7C):**
- Bottom sheet form
- Fields: Payee, Amount, Account, Category, Frequency, Next Date, End (Never/After X/On date)

### 8. Settings (Screen 8)
Sections:
- **Budget:** Accounts, Categories, Payees
- **Sharing:** Shared With, Invite Someone
- **Tools:** AI Assistant, Export Data
- **Account:** Profile, Sign Out

### 8A-8B. Account Management
- List all accounts grouped by type
- Drag to reorder
- Add account: pick type, enter name + starting balance
- Edit account: can close (keeps history, hides from main views)

### 9. Category Management (Screen 9, 9A, 9B)
- All groups and categories with drag handles
- Drag categories between groups
- Tap ⋯ on group → rename, add category, delete
- Tap category → edit name, icon (emoji picker), group, **default amount**
- Default amount is used for projection mode and as reference when budgeting
- "+ Add Category Group" at bottom

### 10. Voice Input (Screen 10A-10D)
**No chat interface — just overlay states:**

**10A. Listening:**
- Pulsing green mic indicator
- Live transcript as you speak
- Tap anywhere to cancel
- Auto-stops after pause

**10B. Processing:**
- Spinner while AI parses
- Shows what was heard

**10C. Success:**
- New items appear highlighted in list
- Toast: "Created 2 transactions" with Undo
- Highlight fades after 2 seconds

**10D. Clarification (only when needed):**
- Simple tap-to-select options
- "Which account?" with account buttons
- Learns from your choice

**Voice can handle:**
- Single/multiple transactions: "I spent $120 at Costco on groceries and $45 at Shell on gas"
- Categories: "Add a category called Pet Supplies in Everyday"
- Recurring: "Netflix $15 monthly on the 14th"
- Budget amounts: "Set groceries to $400 and gas to $150"
- Moving money: "Move $50 from Dining Out to Groceries"

### 11. Onboarding (Screen 11A-11C)
**11A. Welcome:**
- Logo, app name, tagline
- Create Account / Sign In buttons
- Apple / Google sign-in options

**11B. Sign Up:**
- Email + password fields
- Creates account → goes to setup

**11C. Quick Setup (skippable):**
- Step 1: Create account ✓
- Step 2: Add first account (name + balance)
- Step 3: Set up categories (could offer templates)
- "Skip setup" link

### 12. Multi-User Sharing (Screen 12A-12D)
**12A. Sharing Settings:**
- List of people with access (avatar, name, email)
- Owner badge on budget creator
- Remove button for others
- Email input to invite someone
- Pending invites section

**12B. Invite Sent:**
- Toast confirmation
- Appears in pending until accepted

**12C. Email Received (recipient's inbox):**
- Clear sender info
- "Accept Invite" button
- Works for new or existing users

**12D. Accept Invite:**
- Shows who invited them
- "Accept & Sign Up" or "I Have an Account"
- Decline option
- After accept → lands in shared budget

---

## Key Interactions

### Clearing Transactions
- Tap the ○/● dot on any transaction row
- Toggles cleared status instantly
- Toast confirms with Undo option
- Account cleared/uncleared balances update
- Also available in transaction edit screen

### Payee Default Categories
- First transaction with new payee → that category becomes default
- Next time you enter that payee → category auto-fills
- Shows hint: "Default for Costco"
- If you change the category → after save, prompt: "Update default for Costco?" (No/Yes)
- Saying "No" keeps original default (one-off exception doesn't override)

### Transfers
- Select "Transfer" in type toggle
- Form changes to From/To accounts (no payee, no category)
- Creates linked transactions in both accounts
- Transfer amounts show in gray
- On transaction list: "Transfer to Savings" with → indicator

### Moving Money Between Envelopes
- Not a transaction — just adjusting budget amounts
- User can manually edit amounts in budget view
- **Or tap overspent amount → modal shows categories to pull from**
- Shows all categories with surplus, sorted by available amount
- Tap to move funds (partial moves supported)
- Voice shortcut: "Move $50 from Dining to Groceries"
- Both category budgets update, "Ready to Assign" stays same

### Projection Mode
**Purpose:** Plan your budget before the month starts

**Screen layout (sticky header):**
1. Expected Income (editable, pre-fills from default)
2. Hint: "Default: $4,500 · Last month: $4,450"
3. Summary: Total Projected | Left to Allocate
4. Category list with Default | Projected columns (scrollable)

**Flow:**
1. Enter Projection Mode (toggle in budget view)
2. Adjust expected income if needed (pre-filled from budget default)
3. See "Left to Allocate" update as you work
4. Adjust category projections — play with numbers
5. "Apply Projections" → projections become the budget
6. Exit projection mode → live with that budget for the month

**Key behaviors:**
- Income + summary = sticky on scroll (always visible)
- Income pre-fills from `budgets.default_monthly_income`
- Shows last month's income as reference
- "Left to Allocate" = Income - Total Projected (updates live)
- Projections persist until you change them (don't auto-clear)
- First projection auto-fills from default amount
- "Clear All Projections" resets everything to defaults
- Supports 1-3 projection columns (JSON field)
- Mid-month adjustments happen in normal budget view, not projection mode

**Quick Fill Logic (no projections vs projections):**
- If user has never used projections → "Copy Last Month" (actual budgeted amounts)
- If projections exist → "Apply Projections" (with picker if multiple)

### Category Defaults
- Set default amount when creating/editing a category
- Default is your baseline/target for that category
- Shown as reference in projection mode
- First projection auto-fills from default
- Helps you quickly set up a new month's budget

### Split Transactions
- In category field, select "Split Transaction"
- Add multiple category/amount pairs
- Must balance to transaction total
- Yellow = unassigned remaining, Green = balanced
- Saves as one transaction with multiple category allocations

---

## API Endpoints (suggested structure)

### Auth
- POST /auth/register
- POST /auth/login
- POST /auth/logout
- GET /auth/me

### Budgets
- GET /budgets (list user's budgets)
- POST /budgets
- GET /budgets/{id}
- PUT /budgets/{id}
- DELETE /budgets/{id}

### Sharing
- GET /budgets/{id}/members
- POST /budgets/{id}/invite
- DELETE /budgets/{id}/members/{userId}
- POST /invites/{token}/accept

### Accounts
- GET /budgets/{id}/accounts
- POST /budgets/{id}/accounts
- PUT /accounts/{id}
- DELETE /accounts/{id}

### Categories
- GET /budgets/{id}/categories (includes groups)
- POST /budgets/{id}/category-groups
- PUT /category-groups/{id}
- DELETE /category-groups/{id}
- POST /category-groups/{id}/categories
- PUT /categories/{id}
- DELETE /categories/{id}

### Monthly Budgets
- GET /budgets/{id}/monthly/{month}
- PUT /budgets/{id}/monthly/{month} (bulk update)
- POST /budgets/{id}/monthly/{month}/copy-previous

### Transactions
- GET /budgets/{id}/transactions
- POST /budgets/{id}/transactions
- PUT /transactions/{id}
- DELETE /transactions/{id}
- POST /transactions/{id}/clear

### Recurring
- GET /budgets/{id}/recurring
- POST /budgets/{id}/recurring
- PUT /recurring/{id}
- DELETE /recurring/{id}

### Payees
- GET /budgets/{id}/payees
- PUT /payees/{id}

### Voice/AI
- POST /budgets/{id}/voice-parse
  - Input: { transcript: "..." }
  - Output: { actions: [...], clarification_needed: null | {...} }

---

## Implementation Order (suggested)

### Phase 1: Core (MVP)
1. Auth (register, login)
2. Budgets (create, select)
3. Accounts (CRUD, balances)
4. Categories & Groups (CRUD)
5. Transactions (CRUD, cleared status)
6. Basic Budget view (view/edit monthly amounts)

### Phase 2: Essential Features
7. Transfers between accounts
8. Split transactions
9. Recurring transactions
10. Payee management with defaults
11. Copy last month budget

### Phase 3: Polish
12. Voice input with AI parsing
13. Multi-user sharing
14. Onboarding flow
15. Search & filters

### Phase 4: Mobile
16. Capacitor setup
17. iOS build & testing
18. App Store submission

---

## Notes

- **No bank imports** — all manual entry (keeps it simple)
- **Mobile-first** — build for phone, desktop can come later with responsive CSS
- **Real-time sync** — when sharing, both users see updates immediately (consider websockets or polling)
- **Offline support** — consider later, not MVP

---

## Files

- `wireframes.html` — Visual designs for all screens
- This document — How it all works

When building, reference the wireframes for visual details and this guide for logic/data/interactions.
