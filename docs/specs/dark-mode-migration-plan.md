# BudgetGuy Dark Mode Migration

## Context
BudgetGuy currently has a light theme with hardcoded hex colors. The goal is to adopt PropOff's dark mode design system: CSS variables for theme-aware colors, a 5-layer dark surface hierarchy, glow focus effects, button hover inversions, Figtree font, and theme switching (green/blue/orange accent + slate/cream background).

The backward-compatible alias strategy in `tailwind.config.js` means most existing token usages (`bg-surface`, `text-body`, `text-subtle`, `text-income`, etc.) will automatically resolve to dark values without touching Vue files. The bulk of file edits are for: layout chrome, focus ring replacements, hover states, toast backgrounds, hardcoded shadows, and a few `bg-white`/`text-inverse` fixups.

---

## Phase 0: Deliverables & Preview

### 0.1 Save plan to `docs/specs/dark-mode-migration-plan.md`
- Copy this plan into the project docs for reference

### 0.2 Create dark mode wireframe mockup at `docs/specs/dark-mode-wireframes.html`
- Standalone HTML file matching the format of `docs/specs/budget-wireframes-all.html`
- Phone frame mockups with the new dark color palette (PropOff's surface hierarchy)
- Figtree font, glow effects, button hover inversions shown visually
- Screens to include:
  1. **Dashboard** - Account cards on dark surfaces, green FAB with glow
  2. **Budget View** - Month nav, Available to Budget card, category groups with dark rows
  3. **Add Transaction** - Segmented control (expense/income/transfer), form fields on dark surface-inset inputs
  4. **Transaction List** - Search bar, filter chips, date-grouped transactions on dark
  5. **Settings** - Menu items on dark surface, Appearance section with accent color swatches + bg mode toggle
  6. **Login** - Dark guest layout with surface card form, glow focus on inputs, gradient button
  7. **Plan/Projections** - Income input, category projections list
  8. **Move Money Modal** - Dark modal with source/dest category selection
  9. **Theme Switching Preview** - Side-by-side showing green/blue/orange accent + slate/cream backgrounds

---

## Phase 1: Foundation

### 1.1 Rewrite `resources/css/app.css`
- Replace IBM Plex Sans import with Figtree import
- Add `@layer base` with `:root` CSS variables (RGB triplets for all colors)
- Add accent theme classes: `.accent-green`, `.accent-blue`, `.accent-orange`
- Add background mode classes: `.bg-mode-slate`, `.bg-mode-cream`
- Add `@layer components` with `.focus-glow`, `.focus-glow-sm`, `.shadow-glow-primary` utilities
- Set `html` and `body` background to `rgb(var(--color-bg))`
- Keep existing form resets, touch sizing, safe area, and max-w-app rules

### 1.2 Rewrite `tailwind.config.js`
- Switch font from IBM Plex Sans to Figtree
- Replace all hardcoded hex colors with `rgb(var(--color-X) / <alpha-value>)` references
- Add surface hierarchy: `bg`, `surface`, `surface-elevated`, `surface-overlay`, `surface-inset`, `surface-header`
- Add backward-compat aliases: `surface-secondary` → `surface-elevated`, `surface-tertiary` → `surface-overlay`
- Add text tokens: `body`, `muted`, `subtle`, `inverse`
- Map financial colors: `income` → `success` var, `expense` → `danger` var, `transfer` → `info` var
- Keep `secondary` as alias to `info` for backward compat

### 1.3 Update `resources/views/app.blade.php`
- Add `700` weight to Figtree font link (line 18)
- Add default theme classes to `<body>`: `bg-mode-slate accent-green`

### 1.4 Create `resources/js/Composables/useTheme.js`
- Reactive state for `accentColor` (green/blue/orange) and `bgMode` (slate/cream)
- `watchEffect` to apply/remove CSS classes on `document.body`
- localStorage persistence under `budgetguy-theme` key
- Export `useTheme()` with getters and setters

### 1.5 Update `resources/js/app.js`
- Change progress bar color from `#4B5563` to `#57d025`

---

## Phase 2: Layouts

### 2.1 `resources/js/Layouts/AppLayout.vue`
- Line 14: `bg-surface-secondary` → `bg-bg`
- Line 16 (header): `bg-body text-inverse` → `bg-surface-header text-body`
- Line 43 (bottom nav): `bg-surface` → `bg-surface-header`

### 2.2 `resources/js/Layouts/GuestLayout.vue`
- Line 14, 16: `bg-body` → `bg-bg`
- Line 20: `text-inverse` → `text-body`

### 2.3 `resources/js/Layouts/AuthenticatedLayout.vue`
- Line 11: `bg-surface-secondary` → `bg-bg`
- Line 13: `bg-surface` → `bg-surface-header`
- Line 49: `bg-surface` → `bg-surface-header`
- Line 94: `hover:bg-surface-secondary` / `focus:bg-surface-secondary` → `hover:bg-surface-overlay` / `focus:bg-surface-overlay`
- Line 180: `bg-surface shadow` → `bg-surface-header shadow`

---

## Phase 3: Base Components

### 3.1 `Components/Base/Button.vue`
- Replace `focus:ring-2 focus:ring-offset-2` with empty (glow handled per-variant)
- Update all variant classes to PropOff pattern (hover inversion: bg→inverse, text→variant color, 2px border)

### 3.2 `Components/Base/Modal.vue`
- Header div: add `bg-surface-header`
- Close button: `hover:bg-surface-secondary` → `hover:bg-surface-overlay`

### 3.3 `Components/Base/BottomSheet.vue`
- Handle: `bg-border-dark` → `bg-border-strong`

### 3.4 `Components/Base/Toggle.vue`
- Off-state track: `bg-border` → `bg-surface-overlay`
- Thumb: `bg-white` → `bg-inverse`

### 3.5 `Components/Base/FilterChip.vue`
- Inactive: `bg-surface-tertiary` → `bg-surface-overlay`

---

## Phase 4: Form Components

### 4.1 `Components/Form/TextInput.vue`
- `bg-surface` → `bg-surface-inset`
- `focus:border-secondary focus:ring-2 focus:ring-secondary/20` → `focus:border-transparent focus-glow`

### 4.2 `Components/Form/SelectInput.vue`
- Default variant: `bg-surface` → `bg-surface-inset`
- Focus: `focus:border-secondary focus:ring-2 focus:ring-secondary/20` → `focus:border-transparent focus-glow`

### 4.3 `Components/Form/SegmentedControl.vue`
- Track: `bg-surface-secondary` → `bg-surface-inset`

### 4.4 `Components/Form/DateField.vue`
- Selection highlight: `bg-surface-secondary` → `bg-surface-overlay`

### 4.5 `Components/Form/ToggleField.vue`
- Switch on: `bg-income` → `bg-primary`
- Switch off: `bg-border-dark` → `bg-surface-overlay`
- Knob: `bg-white` → `bg-inverse`

### 4.6 `Components/Form/Checkbox.vue`
- Unchecked bg: `bg-surface` → `bg-surface-inset`
- Focus: `peer-focus:ring-2 peer-focus:ring-primary/50` → glow shadow

### 4.7 Other form components
- `FormRow.vue`, `TextField.vue`, `AmountField.vue`, `PickerField.vue`, `AutocompleteField.vue`, `InputLabel.vue`, `InputError.vue`: All use semantic tokens that auto-resolve. No changes needed.

---

## Phase 5: Domain Components

### 5.1 `Components/Domain/AccountCard.vue`
- `hover:bg-surface-secondary` → `hover:bg-surface-overlay`

### 5.2 `Components/Domain/FAB.vue`
- Add glow shadow: `shadow-lg hover:shadow-xl` → `shadow-glow-primary hover:shadow-glow-primary-hover`

### 5.3 `Components/Domain/AvailableToBudget.vue`
- No changes needed (gradient + `text-body` auto-resolve)

---

## Phase 6: Page Files

### 6.1 Bulk patterns across all pages

| Pattern | Replace with | ~Count |
|---------|-------------|--------|
| `bg-body text-inverse` (toasts) | `bg-surface-header text-body` | ~9 |
| `bg-surface-secondary` (headers/rows) | `bg-surface-header` | ~8 |
| `hover:bg-surface-secondary` | `hover:bg-surface-overlay` | ~40 |
| `shadow-[0_8px_16px_-4px_rgba(126,217,87,*)]` | `shadow-glow-primary` | ~5 |
| `hover:shadow-[...]` (old green glow) | `hover:shadow-glow-primary-hover` | ~5 |
| `focus:ring-2 focus:ring-primary` (inline) | `focus-glow` | ~10 |
| `bg-white` (hardcoded) | `bg-surface` or `bg-inverse` | ~5 |

### 6.2 Key pages with inline color fixes
- **Budget/Index.vue**: Toast colors, month nav hover, summary header rows, context menu hover, move money modal, column headers
- **Transactions/Index.vue**: Toast, search input focus, filter buttons
- **Transactions/Create.vue & Edit.vue**: Form container backgrounds
- **Plan/Index.vue**: Toast, dropdown hover
- **Settings/Index.vue + sub-pages**: All `hover:bg-surface-secondary`
- **Auth pages** (Login, Register, ForgotPassword, ResetPassword, ConfirmPassword, VerifyEmail): Hardcoded shadow glows, link colors
- **Onboarding pages**: `bg-body` → `bg-bg`, `text-inverse` → `text-body`
- **Profile pages**: Focus rings on inline inputs, `bg-white` → `bg-surface`

### 6.3 Add theme settings UI to `Settings/Index.vue`
- Import `useTheme` composable
- Add "Appearance" section with accent color swatches (green/blue/orange) and background mode options (Slate/Cream)

---

## Verification
1. Run `npm run dev` after Phase 1 — app should immediately render dark
2. After Phase 2 — header, bottom nav, and auth pages should look correct
3. After Phase 3-5 — open Transaction Create (form-heavy) and Budget Index (data-heavy) to verify components
4. After Phase 6 — full walkthrough of every page; test theme switching in Settings
5. Test in both slate and cream background modes
6. Check Capacitor iOS build if available (safe areas, status bar)
