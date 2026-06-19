# Phase 2 — Dashboard, Exámenes, Clínicas

## Tasks

### 2.1 Dashboard
- [x] Replace `resources/views/dashboard.blade.php` with clean dashboard layout (welcome banner, quick actions bar, KPI placeholder grid)

### 2.2 Examenes Index
- [x] Update table rows with `hover:bg-indigo-50/50 transition-colors`
- [x] Update badges with `rounded-full px-3 py-1 text-xs font-medium`
- [x] Update action icons with `w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center`
- [x] Update "Nuevo Examen" button with `rounded-xl`

### 2.3 Clinicas Index
- [x] Update table rows with `hover:bg-indigo-50/50 transition-colors`
- [x] Update action icons with `w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center`

### 2.4 Clinicas Create/Edit
- [x] Update form layout to `max-w-lg mx-auto` centered card
- [x] Update form inputs with `rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200`
- [x] Update submit button with `rounded-xl w-full sm:w-auto`
- [x] Use `gap-5` instead of `gap-4` in form layout

### 2.5 Global Card Pattern
- [x] Replace `shadow-sm sm:rounded-lg` with `rounded-2xl shadow-md border border-gray-100` across all views

## Phase 4 — Users, Reports, Balances, Billing

### 4.1 Users Index
- [x] Table rows: `hover:bg-indigo-50/50 transition-colors`
- [x] Role badges: `rounded-full px-3 py-1 text-xs font-medium` with icon (admin: shield, usuario: person)
- [x] Action buttons: `w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center`
- [x] Modals: `rounded-2xl shadow-xl`
- [x] Mobile cards: `rounded-2xl shadow-md border border-gray-100`

### 4.2 Reportes Index
- [x] Cards: `rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300`
- [x] Add Material Symbol icon to each card header
- [x] Breadcrumbs: `text-sm text-gray-500`

### 4.3 Balances Index
- [x] KPI cards: `rounded-2xl shadow-md border border-gray-100`
- [x] Table: `rounded-xl overflow-hidden`
- [x] Breadcrumbs: `text-sm text-gray-500`

### 4.4 Billing
- [x] Verify no old card patterns remain — already uses `rounded-xl`/`rounded-2xl` with `shadow-sm` and `border-gray-200`

### 4.5 SaaS Admin Views
- [x] All buttons: `rounded-xl` (replaced `rounded-lg`/`rounded-md`)
- [x] Consistent card/table patterns

### 4.6 SaaS Layout
- [x] Verify uses new design system classes — already modern with Material Symbols, PWA, flex layout

# Phase 3 — Repases Views

## Tasks

### 3.1 Repases Index
- [x] Update table rows with `hover:bg-indigo-50/50 transition-colors`
- [x] Update estado badges with `rounded-full px-3 py-1 text-xs font-medium` and proper colors
- [x] Update mobile cards with `rounded-2xl shadow-md border border-gray-100`
- [x] Verify filter buttons and form: `rounded-xl` inputs already in place

### 3.2 Repases Create
- [x] Update all inputs, selects with `rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200`
- [x] Add section header icons via inline SVG
- [x] Update collapsible section buttons with `rounded-xl` and softer colors

### 3.3 Repases Show
- [x] Verify cards already have `rounded-2xl shadow-md border border-gray-100` (from Phase 2)
- [x] Update action buttons to `rounded-xl`
- [x] Update table wrappers with `rounded-xl overflow-hidden`
- [x] Update gasto section headers to `rounded-xl`

### 3.4 Repases Edit
- [x] Update all inputs, selects with `rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200`
- [x] Add section header icons via inline SVG
- [x] Update collapsible section buttons with `rounded-xl` and softer colors

### 3.5 Agenda Views
- [x] Update `agendas/index.blade.php` — buttons, form inputs, modal containers
- [x] Update `calendario/index.blade.php` — FullCalendar container with `rounded-2xl shadow-md border border-gray-100`

### 3.6 Agendas Create/Edit
- [x] Check `resources/views/agendas/` — no separate create/edit views exist (handled via modals in index)

## Verification
- [x] Run `php artisan test` — no regressions (pre-existing 23 failures unchanged, 378+ tests passing)
- [x] Run `php artisan test` for Phase 4 — no regressions (pre-existing 23 failures unchanged, 378 tests passing)

# Phase 5 — Toast Notifications, Empty States, Confirmations, Skeleton Loaders

## Tasks

### 5.1 Flash Messages → Toasts
- [x] Replace flash message divs in `resources/views/layouts/app.blade.php` with Alpine.js toast notification system
- [x] Replace flash message divs in `resources/views/layouts/saas.blade.php` with Alpine.js toast notification system

### 5.2 Empty States
- [x] Create reusable `resources/views/components/empty-state.blade.php` component
- [x] Use empty-state component in `resources/views/repases/index.blade.php` (when no repases)
- [x] Use empty-state component in `resources/views/examenes/index.blade.php` (when no examenes)
- [x] Use empty-state component in `resources/views/balances/index.blade.php` (when no balances)

### 5.3 Confirmation Modals
- [x] Create reusable `resources/views/components/confirm-modal.blade.php` component
- [x] Use confirm-modal in `resources/views/users/index.blade.php` for delete user action

### 5.4 Skeleton Loaders
- [x] Add `@keyframes shimmer` animation CSS to `resources/css/app.css`
- [x] Add `.skeleton` utility class with shimmer animation to `resources/css/app.css`

### 5.5 Smooth Scroll & Back to Top
- [x] Add Alpine.js scroll-to-top button to `resources/views/layouts/app.blade.php` before `</body>`

## Verification
- [x] Run `php artisan test` — no regressions (pre-existing 23 failures unchanged, 389 tests passing)
- [x] New Phase 5 tests pass: 11/11 (38 assertions)
