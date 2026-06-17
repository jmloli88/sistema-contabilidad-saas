# Empresa Subscription Specification

## Purpose

Shift billing from per-user clinic-shared subscriptions to per-empresa subscriptions, with a dual-path transition that preserves access during migration.

## Requirements

### Requirement: Per-Empresa Subscription Storage

The `subscriptions` table MUST accept a nullable `empresa_id` foreign key. An empresa subscription SHALL be the primary billing unit — one active subscription grants access to ALL users of that empresa. Per-user subscriptions MAY coexist during the transition period.

#### Scenario: Create subscription for an empresa

- GIVEN empresa E has id 1
- WHEN a Stripe subscription is created targeting empresa E
- THEN a subscription record with `empresa_id = 1` is persisted
- AND `user_id` is NULL for empresa-level subscriptions

### Requirement: Empresa Subscription Check

`EnsureSubscriptionIsActive` middleware MUST check `$user->empresa->hasActiveSubscription()`. This method SHALL return true when the empresa has at least one subscription with `stripe_status` in `['active', 'trialing']` and `ends_at` is NULL or in the future.

#### Scenario: Active empresa subscription grants access

- GIVEN user U belongs to empresa E with an active Stripe subscription
- WHEN U accesses a `subscription`-protected route
- THEN the middleware allows the request

#### Scenario: Expired empresa subscription denies access

- GIVEN user U belongs to empresa E whose subscription `ends_at` is in the past
- WHEN U accesses a `subscription`-protected route
- THEN the middleware redirects to the billing page with an "subscription expired" message

### Requirement: Dual-Path Transition

During Phase 3–4 transition, the subscription check MUST use a dual-path strategy: first check empresa-level subscription, then fall back to the legacy clinic-shared check (`hasActiveSubscriptionInClinic()`). The system SHOULD log which path resolved each access. After Phase 5, the clinic-shared fallback SHALL be removed.

#### Scenario: Empresa subscription resolves access

- GIVEN empresa E has an active subscription; no user in the clinic has one
- WHEN a user from that empresa accesses a protected route
- THEN the empresa path resolves and access is granted
- AND the log records "subscription_path: empresa"

#### Scenario: Clinic-shared fallback during transition

- GIVEN empresa E has no subscription; another user in the same clinic has an active per-user subscription
- WHEN a user accesses a protected route during the transition
- THEN the clinic-shared fallback grants access
- AND the log records "subscription_path: clinic-shared"

#### Scenario: Fallback removed post-transition

- GIVEN Phase 5 is deployed and clinic-shared fallback is removed
- WHEN only a per-user subscription exists but no empresa subscription
- THEN access is denied
