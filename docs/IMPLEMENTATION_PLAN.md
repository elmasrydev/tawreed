# TawreedHub — Web Prototype Implementation Plan

Stack as built: PHP 8.4, Laravel 13.30, Filament 5.7 for the admin dashboard, MySQL 9, Livewire 4.4 + Blade + Tailwind 4 for the buyer/supplier web app. Runs locally on Laravel Herd at `tawreed.test`.

> Version note: this plan was first drafted against Laravel 12 / Filament 4. The build uses the current releases, Laravel 13 and Filament 5, which changed several conventions: models declare `#[Fillable]`/`#[Hidden]` PHP attributes instead of properties, controllers no longer carry `AuthorizesRequests` (use the `Gate` facade), and Livewire 4 components are single-file Blade files discovered from `resources/views/livewire`.

## Build status

| Phase | State |
|---|---|
| 0 · Project setup | Done |
| 1 · Foundation: auth, roles, reference data | Done |
| 2 · Buyer: registration, RFQ wizard, requests | Done |
| 3 · Supplier: registration, verification, feed, quote | Done |
| 4 · Quote lifecycle, anonymity, comparison | Done |
| 5 · Chat | Done, polling; Reverb still optional |
| 6 · Subscriptions, gating, invoices | Done |
| 7 · Filament admin dashboard | Done |
| 8 · Notifications, demo data, hardening | Demo seeder and tests done; notifications outstanding |

Test suite: 127 Pest tests, 372 assertions, all passing. Pint clean.

Demo accounts, password `password`: `admin@tawreedhub.test`, `buyer@tawreedhub.test`, `supplier@tawreedhub.test`.

## 1. Architecture decision

**Recommended: two front ends on one Laravel app.**

| Surface | Tech | URL | Why |
|---|---|---|---|
| Buyer & supplier app | Blade + Livewire 4 single‑file components + Tailwind 4, responsive | `/` , `/buyer/*`, `/supplier/*` | Full‑width responsive web layout: header navigation on desktop, tab bar on small screens. RTL throughout. |
| Admin dashboard | Filament panel | `/admin` | Filament gives resources, tables, filters, widgets, charts and actions out of the box; every admin screen in the prototype maps to a Filament resource or page. |

Fallback if speed matters more than fidelity: build buyer and supplier as two extra **Filament panels** (`/buyer`, `/supplier`). Same models and policies; you lose the app‑like look but ship in roughly half the front‑end time. Decide at Phase 2; the domain layer (Phase 1) is identical either way.

Key packages
- `filament/filament` — admin panel.
- `livewire/livewire` 4 — app screens as single-file components. Volt is not needed; Livewire 4 has single-file components natively.
- `spatie/laravel-medialibrary` — RFQ attachments, supplier docs, logo, portfolio, message files.
- A `role` enum on `users` plus policies — Buyer / Supplier / Admin. `spatie/laravel-permission` was not needed for three fixed roles.
- `barryvdh/laravel-dompdf` — e‑invoice PDFs.
- `laravel/reverb` + `laravel/echo` — real‑time chat (Phase 5b; the thread currently uses `wire:poll`).
- `mcamara/laravel-localization` or a small `SetLocale` middleware — `/ar/...` and `/en/...` URLs, `dir` attribute on `<html>`.
- `laravel/pint`, `larastan`, `pestphp/pest` — quality.

## 2. Data model (MySQL)

```
users                 id, name, email, phone, password, role[buyer|supplier|admin], locale[ar|en],
                      phone_verified_at, status[active|suspended], last_seen_at, timestamps
otp_codes             id, user_id, code, expires_at, consumed_at

business_types        id, slug, name_ar, name_en, sort
governorates          id, slug, name_ar, name_en
categories            id, parent_id (null = main), slug, name_ar, name_en, sort, is_active
units                 id, slug, name_ar, name_en

buyer_profiles        id, user_id, business_type_id, governorate_id, company_name, company_address,
                      job_title, commercial_reg_no (null), tax_card_no (null)
supplier_profiles     id, user_id, company_name, commercial_reg_no, tax_number, facility_address,
                      activity_description, payment_method, years_active, verification_status
                      [pending|verified|rejected|suspended], verification_note, verified_at,
                      rating_avg (cached), reviews_count (cached), completed_deals_count (cached)
supplier_governorate  supplier_profile_id, governorate_id           (coverage pivot)
supplier_documents    id, supplier_profile_id, type[commercial_reg|tax_card|logo|portfolio],
                      media via medialibrary, status[pending|accepted|rejected], note

rfqs                  id, buyer_id (users), category_id, subcategory_id, title, specs, quantity,
                      unit_id, governorate_id, delivery_date, supply_type[one_time|recurring],
                      recurrence_note, quote_deadline, notes, status[draft|open|ending_soon(derived)
                      |awarded|closed|expired|removed], quotes_count (cached), best_price (cached),
                      awarded_quote_id, published_at, timestamps, soft deletes
  media: attachments

quotes                id, rfq_id, supplier_id (users), unit_price, total_price, min_order_qty,
                      vat_mode[included|excluded], vat_amount, delivery_cost, expected_delivery_date,
                      validity_days, payment_terms, sample_availability, brand_origin, extra_specs,
                      warranty_policy, status[pending|shortlisted|selected|rejected|withdrawn|expired],
                      rejection_reason_id, selected_at, rejected_at, timestamps
rejection_reasons     id, slug, name_ar, name_en

conversations         id, rfq_id, quote_id, buyer_id, supplier_id, opened_at, last_message_at
messages              id, conversation_id, sender_id, type[text|file|system|sample_request], body,
                      read_at, timestamps         media: file
reviews               id, supplier_id, buyer_id, rfq_id, rating 1‑5, body, timestamps

plans                 id, slug[15d|1m|1y], name_ar, name_en, days, price_egp, is_best_value,
                      trial_days, is_active
subscriptions         id, supplier_id, plan_id, starts_at, ends_at, status[trial|active|expiring
                      (derived)|expired|cancelled], auto_renew, payment_ref, timestamps
invoices              id, subscription_id, number (INV‑YYYY‑NNNN), amount_egp, vat_egp, issued_at,
                      pdf via medialibrary

reports               id, reporter_id, reportable_type/id (quote|rfq|message|user), type, reason,
                      status[open|removed|dismissed], resolved_by, resolved_at
notifications         Laravel database notifications
audit / activity_log  optional (spatie/laravel-activitylog) for admin actions
```

Derived rules
- RFQ `ending_soon` = open and `quote_deadline` within 48h; `expired` = deadline passed with no award (scheduled job).
- Supplier `has_active_subscription()` = a subscription with `ends_at > now()` and status in `trial|active`.
- `Quote::isVisibleTo(User)` and `Rfq::buyerSnapshotFor(User)` implement the visibility matrix in `docs/FEATURES.md`. **All anonymisation lives in policies + API resources/view‑models, never in Blade.**

## 3. Phased plan

Estimates are for one full‑stack Laravel developer. Total ≈ 30–36 working days for a demo‑quality prototype.

### Phase 0 — Project setup (1–2 days)
- `laravel new tawreed` on Herd, MySQL database `tawreed`, `.env`.
- Install Filament, Livewire/Volt, Tailwind (with `tailwindcss-rtl` or logical properties), medialibrary, dompdf, Pest.
- Localization: `lang/ar.json`, `lang/en.json` seeded from the prototype's `t` dictionary (both languages are already written in the prototype — copy them). `SetLocale` middleware, `<html dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">`, IBM Plex Sans Arabic via Google Fonts.
- Design tokens from the prototype: primary `#1B6FE0`, teal accent `#0E9F9F`, ink `#10233F`, muted `#5B6B80`, border `#DDE5EF`, bg `#E8EDF4`; status colours (open teal, review amber, rejected red).
- Base layout: mobile‑frame container (max‑width ~430px centred on desktop), bottom tab bar component, toast component (Livewire event → Alpine).

### Phase 1 — Foundation: auth, roles, reference data (3 days)
- Migrations + models + factories for everything in §2.
- Seeders: business types (11), governorates (27), categories (6 main + subs), units, rejection reasons, plans (3), an admin user.
- Auth: registration split by role (buyer / supplier), login, password reset, **OTP** (`otp_codes`, fake SMS driver that logs the code; interface for a real provider later).
- Role middleware + `UserPolicy`; suspended users are blocked at login.
- Splash screen (`/`): account type choice, language toggle, admin link.

### Phase 2 — Buyer: registration, RFQ wizard, my requests (5 days)
- Buyer registration form with business type chips and privacy note.
- Create RFQ as a 3‑step Livewire wizard with per‑step validation, draft persisted in session or a `draft` RFQ row; attachments via medialibrary temporary uploads.
- Publish ⇒ `RfqPublished` event ⇒ notify subscribed + matching suppliers (category/coverage).
- Buyer home (stat tiles, list), My Requests (status pills), Account tab.
- Scheduled command `rfqs:expire` (deadline passed ⇒ `expired`).

### Phase 3 — Supplier: registration, verification, feed, quote (5 days)
- Supplier registration: profile fields, document uploads, coverage chips, submit ⇒ `verification_status = pending`, OTP.
- Verification status banner with hints (`verified/pending/rejected/suspended`); unverified suppliers can browse but **cannot submit quotes** (policy).
- RFQ feed: anonymised cards (entity type + governorate only), filters by category / governorate / deadline, search.
- Submit quote form (all fields from the prototype) with one‑quote‑per‑RFQ rule and **contact‑detail detector** (regex for phone/email in free text ⇒ reject or flag for moderation).
- My Quotes tab with statuses.

### Phase 4 — Quote lifecycle, anonymity, supplier profile (4 days)
- Buyer RFQ detail: quote cards with all detail rows; actions *Shortlist*, *Reject (reason)*, *Select*.
- `QuoteSelected` action: transaction that sets quote `selected`, RFQ `awarded`, other pending quotes `not selected`, creates `conversation`, fires notifications.
- Quotes inbox grouped by RFQ with best price.
- Supplier public profile page: info rows, portfolio, anonymised reviews; buyer can leave a review after a deal is awarded.
- Visibility layer: `QuotePolicy`, `RfqPolicy`, view‑models that strip buyer identity unless `selected && supplier subscribed`. Pest tests for every cell of the visibility matrix.

### Phase 5 — Chat (3 days)
- Conversation list (buyer + supplier tabs) with unread counts and empty/locked states.
- Message thread: text, file attachments, system messages ("chat opened, contact details revealed"), *Request sample* quick action, read receipts.
- 5a: `wire:poll.3s` for the prototype. 5b (optional): Reverb + Echo for real‑time and "online now" presence.

### Phase 6 — Subscriptions, gating, invoices (4 days)
- Plans page, current‑plan card, expiry banner (≤ 7 days), auto‑renew toggle, "paused not deleted" copy.
- `SubscribeToPlan` action with a **fake payment gateway** interface (`PaymentGateway::charge()` returns success); leave adapters for Paymob / Fawry for later.
- Invoice generation (`INV‑YYYY‑NNNN`) + dompdf PDF + list with download.
- Gating: `EnsureSupplierSubscribed` middleware/policy checks on chat, buyer details, selected‑quote unlock; locked variants of the selected‑quote card and chat tab.
- Scheduled jobs: `subscriptions:expire`, `subscriptions:notify-expiring` (7 and 1 days before), optional auto‑renew.

### Phase 7 — Filament admin dashboard (5 days)
- Panel at `/admin`, admin‑only guard, AR/EN toggle (Filament supports RTL natively).
- **Dashboard page:** stat widgets (active RFQs, quotes submitted, active subscriptions, monthly revenue with week/month deltas), weekly revenue chart widget, verification queue table widget.
- **Resources:** `SupplierResource` (verification queue tab: approve / reject with note / suspend; document viewer), `UserResource` (search by name/phone/comm. reg.; suspend/unsuspend), `BuyerResource`, `RfqResource` (view, close, remove), `QuoteResource` (read‑only + remove), `CategoryResource` (tree, RFQ counts, add), `PlanResource`, `SubscriptionResource` (stats header: active / expiring 7d / expired; actions: extend, view invoice), `InvoiceResource`, `ReportResource` (remove content + notify / dismiss), `ConversationResource` (read‑only moderation view).
- Notifications to users on every admin decision.

### Phase 8 — Notifications, demo data, hardening (3–4 days)
- Database + mail notifications for: new quote, quote selected, verification decision, subscription expiring/expired, report resolved; in‑app bell on both apps.
- Demo seeder that recreates the prototype's world (Cafe City, Delta Coffee Roasters, Nile Mills, the 6 RFQs, quotes, chats, subscriptions, reports) so the web prototype demos exactly like the mobile one.
- Pest feature tests: registration, RFQ wizard, quote submission rules, selection flow, gating, admin actions.
- Accessibility + RTL QA pass in both languages; Pint + Larastan clean; README with setup steps.

## 4. Route map

```
GET  /                         splash (account type + language)
GET  /register/buyer  /register/supplier   POST …   OTP: /verify-phone
GET  /login  POST /login  POST /logout

/buyer            home   /buyer/rfqs   /buyer/rfqs/create (wizard)   /buyer/rfqs/{rfq}
/buyer/quotes     inbox  /buyer/chats  /buyer/chats/{conversation}   /buyer/account
/suppliers/{supplier}     public profile

/supplier         feed   /supplier/rfqs/{rfq}   /supplier/rfqs/{rfq}/quote (form)
/supplier/quotes  /supplier/chats  /supplier/chats/{conversation}
/supplier/subscription   /supplier/subscription/plans/{plan}/subscribe   /supplier/invoices/{invoice}
/supplier/account

/admin/*          Filament panel
```

## 5. Milestones / demo checkpoints

| Milestone | After phase | You can demo |
|---|---|---|
| M1 | 2 | Buyer registers, creates and publishes an RFQ in AR and EN. |
| M2 | 4 | Supplier registers, is verified by admin, quotes; buyer compares, rejects, selects. |
| M3 | 6 | Chat opens after selection; subscription gating and invoices work. |
| M4 | 8 | Full admin dashboard, notifications, seeded demo world, tests green. |

## 6. Out of scope for the prototype (note for v1)
Real SMS OTP provider, real payment gateway (Paymob/Fawry), push notifications, native mobile apps (the Blade app is mobile‑first and can be wrapped in a PWA), advanced search (Scout/Meilisearch), multi‑currency.
