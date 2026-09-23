# TawreedHub: New UI implementation plan

**Source:** the design prototypes in `docs/design/tawreed-new-ui/` (moved there after implementation).
**Scope:** re-skin the existing product. It is **not** a feature expansion. Anything the design shows that the app does not already support is left out here and logged in [`NEW_UI_SUGGESTIONS.md`](NEW_UI_SUGGESTIONS.md).

---

## 1. What the new UI folder contains

The prototypes are "dc" files: markup inside `<x-dc>`, React-style logic in a `data-dc-script` block, and a `screen` prop that switches between screens. All data is hard-coded mock data. The EN/AR switch is a runtime DOM dictionary (`i18n*.js`), so the files are reference material only. Nothing in them can be reused as code.

| File | What it is | Screens |
|---|---|---|
| `Landing Page.dc.html` | Public marketing page, fixed 1280px wide | Nav, Hero, Trust strip, How it works, For buyers / For suppliers, Categories, Pricing, Testimonials, FAQ, Final CTA, Footer |
| `Auth Onboarding.dc.html` | Split screen: 480px navy brand panel plus a form panel | login, forgot, role, account, sup-company, sup-cats, sup-docs, sup-plan, buy-biz, buy-prefs, done |
| `Buyer App.dc.html` | App shell: 232px navy sidebar and a 60px top bar | dash, create (4-step wizard), rfqs, detail (quote compare with table/cards views, reject and award modals), supplier (public profile), messages, quotes, suppliers (directory), settings |
| `Buyer App Shell Options.dc.html` | Shell explorations 2a–2e | **2a (dark rail) + Layout A** is the one the Buyer App actually uses |
| `Supplier App.dc.html` | Same shell. An `accountState` prop drives banners and quote locking | dash, browse, detail, quote drawer, "quote selected" modal, quotes, sub, messages, profile, settings |
| `Admin Dashboard.dc.html` | Shell with a darker `#111827` sidebar and an "ADMIN" pill | overview, queue, review, suppliers, buyers, account, rfqs, rfqdetail, quotes, subs, cats, reports, mod, content, settings, admins |
| `TawreedHub Design System.dc.html` | Tokens and component spec | Brand, color, type, buttons, inputs, badges, RFQ / quote / supplier cards, nav, toasts, chat, empty state, table |
| `Zero Commission Icon Options.dc.html` | Icon exploration | Option **1D** (scalloped "0%" badge) is the one used on the landing page |
| `assets/` | Logos (full-color and white, mark and full), 8 illustration PNGs, 2 role SVGs, the hero photo | |
| `.image-slots.state.json` | 8 base64 category photos for the landing page | cat-food, packaging, cleaning, construction, office, medical, bakery, coffee |
| `uploads/` | Brand source material. Not referenced by any page | |

### Findings that shape the plan

1. **No screen in the design is responsive.** Every file is fixed-width or has `min-width:1024px`, and none has a media query. The mobile layouts are ours to design from the desktop language (see §5).
2. **The new palette replaces the current one entirely.** The current `resources/css/app.css` tokens (`#1B6FE0` blue and `#0E9F9F` teal) come from the old mobile prototype.
3. **The design system is consistent across all files.** The design-system file is the source of truth for tokens and component styles.
4. **The prototypes have their own inconsistencies**, listed in §8 so we pick one answer instead of copying them.

---

## 2. Design tokens (Tailwind v4 `@theme`)

These tokens replace the current `@theme` block in `resources/css/app.css`.

| Group | Tokens |
|---|---|
| Brand | navy `#0B3D5C` (primary-700) · blue `#1565A0` (primary-500) · tint `#E3F0FA` (primary-100) · info border `#BFDBFE` |
| Accent (orange CTA) | `#F28C28` · hover `#E07A15` · 50 `#FFF7ED` · 200 `#FED7AA` · text `#9A3412` |
| Success (teal) | `#17A398` · hover `#128A80` · 50 `#D9F2EF` · 25 `#F0FAF9` · text `#0F7A72` · border `#A7E0DA` |
| Warning (amber) | `#D97706` · 100 `#FEF3C7` · text `#92400E` · border `#FDE68A` · shortlisted card border `#F59E0B` |
| Danger | `#DC2626` · hover/text `#B91C1C` · 50 `#FEF2F2` · 100 `#FEE2E2` · 200 `#FECACA` · dark `#991B1B` |
| Neutrals | Tailwind gray 50–900 (identical to the design values). App bg `#F9FAFB`. Admin sidebar `#111827` |
| Fonts | `Inter` (Latin) + `IBM Plex Sans Arabic` (Arabic), tabular numbers everywhere. Loaded with the existing `bunny()` font helper in `vite.config.js`, so no new dependency |
| Radii | 4 / 6 / 8 (controls) / 10 (cards) / 12 (modals, bubbles) / 16 (marketing cards) / 999 (pills) |
| Shadows | focus `0 0 0 3px #E3F0FA` · error focus `0 0 0 3px #FEE2E2` · card hover `0 4px 16px rgba(11,61,92,.08)` · dropdown `0 12px 32px rgba(17,24,39,.15)` · modal `0 24px 64px rgba(0,0,0,.25)` · toast `0 8px 24px rgba(17,24,39,.3)` |
| Type | H1 24/700 −0.3px in-app (40/700 marketing) · section title 15–16/600 · body 14 · label 13/600 · meta 12 · eyebrow 11–12/600 uppercase · KPI 30/700 |
| Control heights | input 40 · button lg 44 / md 36 / sm 28 · badge 22–24 · chip 26–28 · toggle 34×20 |

### Icons

All icons are inline Lucide/Feather-style SVGs: 24 viewBox, stroke 2, round caps. There is **one `<x-lucide name="…" />` Blade component** (`<x-icon>` is taken by Filament's blade-icons) that holds the path data for the roughly 45 icons the design uses. No icon package is added. The current layouts already inline raw path strings, so this follows that convention.

### Assets

`tawreed-new-ui/assets/*` were copied to `public/images/brand/`. The 8 base64 category photos were exported to `database/seeders/images/categories/*.webp` and are attached to categories by the demo seeder; admins upload category images in Filament. The `uploads/` folder is not needed at runtime.

---

## 3. Page → URL mapping

The **Status** column uses these values:
- ✅ **Re-skin**: the route exists; only the view changes.
- 🔀 **Restructure**: the route exists, but the page layout changes meaningfully.
- ❓ **Needs decision**: see the questions in §9.
- ⏭ **Skip**: the feature does not exist in the app yet, so it is logged in the suggestions file.

### 3.1 Public and auth

| Design screen | Our URL | Route name | Current view | Status |
|---|---|---|---|---|
| Landing Page (all sections) | `/` | `splash` | `splash.blade.php` | 🔀 Replaces the splash. Pricing is read from the `plans` table and stats come from the DB (see Q3 and Q4) |
| Auth › login | `/login` | `login` | `auth/login.blade.php` | ✅ Split layout. Keep our `login` field (phone **or** email), see Q6 |
| Auth › forgot | none | none | none | ⏭ No password reset exists |
| Auth › role | `/register` (new, view-only) | `register` | none | ❓ Q2 |
| Auth › account + buy-biz | `/register/buyer` | `register.buyer` | `auth/register-buyer.blade.php` | 🔀 Same single POST, shown as a client-side stepper (Alpine) |
| Auth › buy-prefs | none | none | none | ⏭ The buyer-interests step is not stored anywhere |
| Auth › account + sup-company + sup-cats + sup-docs | `/register/supplier` | `register.supplier` | `auth/register-supplier.blade.php` | 🔀 Same single POST shown as a 4-step stepper. Our existing fields only |
| Auth › sup-plan | `/supplier/subscription` (after login) | `supplier.subscription` | none | ⏭ Plan and payment at signup is logged as a suggestion. Our subscription page keeps this role |
| (no design) OTP | `/verify-phone` | `phone.verify` | `auth/verify-phone.blade.php` | ✅ Built in the auth split layout, using the design's input and button styles |
| Auth › done | shown after OTP success | none | none | ❓ Q2 (optional "you're in" confirmation) |

### 3.2 Buyer (`/buyer/*`), shell 2a

| Design screen | Our URL | Route name | Status | Notes |
|---|---|---|---|---|
| dash | `/buyer` | `buyer.home` | 🔀 | KPI cards use data we have: open RFQs, new quotes, unread messages, awarded this month. "Recent quotes" table. "Needs attention" shows RFQs closing within 48h and open RFQs with zero quotes (no "extend deadline" action) |
| create (4 steps) | `/buyer/rfqs/create` | `buyer.rfqs.create` | 🔀 | Our Livewire wizard keeps steps 1–3 with the current fields. It adds step 4, **Review & post**, with the "what suppliers will see" preview; this is UI only. "Save draft" is included only if the wizard already persists drafts (see Q9) |
| rfqs | `/buyer/rfqs` | `buyer.rfqs.index` | ✅ | Status tabs with counts from our statuses. Table on desktop, cards on mobile |
| detail + quote compare | `/buyer/rfqs/{rfq}` and `/buyer/rfqs/{rfq}/quotes` | `buyer.rfqs.show` / `buyer.rfqs.quotes` | ❓ | Q5: merge the quotes Livewire component into the detail page as the design does |
| Reject modal | inside the quotes component | none | ✅ | Reasons come from the `rejection_reasons` table |
| Award / select modal | inside the quotes component | none | ✅ | The "revealed to the supplier" box shows our buyer profile fields |
| Shortlist | inside the quotes component | none | ✅ | `ShortlistQuote` exists. The shortlist side card is included; "Compare selected" is skipped |
| supplier (public profile) | none | none | ❓ | Q7. The data mostly exists (profile, reviews, coverage, logo), but there is no page or route |
| messages | `/buyer/chats`, `/buyer/chats/{conversation}` | `buyer.chats.*` | 🔀 | Two-pane on desktop; list → thread on mobile. System message, bubbles, file messages and the **Request sample** quick action are kept, since the `SampleRequest` message type exists |
| quotes | `/buyer/quotes` | `buyer.quotes.index` | ✅ | Tabs by quote status |
| suppliers (directory) | none | none | ⏭ | |
| settings | `/buyer/account` | `buyer.account` | 🔀 | Organisation and Account tabs show our existing data read-only, plus the language switch and logout. The Privacy and Notifications tabs are skipped |
| Top bar: search, bell and dropdown | none | none | ⏭ | Hidden. Only the language toggle stays |
| Sidebar "New RFQ" button and user card | layout | none | ✅ | |

### 3.3 Supplier (`/supplier/*`), same shell

| Design screen | Our URL | Route name | Status | Notes |
|---|---|---|---|---|
| dash | none today | none | ❓ | Q8. KPIs come from our data: matching open RFQs, quotes submitted, selected, win rate. Verification and plan cards |
| browse | `/supplier` | `supplier.feed` | 🔀 | Row cards with the anonymous buyer line, countdown chip and quote button. Filters are limited to what `FeedController` supports today; the extra filters (qty range, sort, saved) are skipped |
| detail | `/supplier/rfqs/{rfq}` | `supplier.rfqs.show` | ✅ | Facts, specs, notes, attachments. "Anonymous buyer" card with locked chips. The gate bar shows when the supplier is unverified or unsubscribed. "Ask a clarification" and the Competition analytics card are skipped |
| Quote drawer | `/supplier/rfqs/{rfq}/quote` | `supplier.rfqs.quote` | ❓ | Q10. Form plus live total summary. Uses **our** fields (VAT mode included/excluded + amount), not the design's VAT % |
| "Quote selected" celebration | none | none | ⏭ | Needs real-time or notifications. The locked/unlocked selected-quote card on My Quotes already covers this |
| quotes | `/supplier/quotes` | `supplier.quotes.index` | ✅ | Tabs by status, reason under the pill, "Open chat" on selected rows. Edit and Withdraw are skipped (no action exists) |
| sub | `/supplier/subscription` | `supplier.subscription` | ✅ | Navy current-plan card with progress, auto-renew toggle, plan tiles, invoices with PDF. The payment-methods card and the reminders card are skipped |
| messages | `/supplier/chats/*` | `supplier.chats.*` | 🔀 | Same component as the buyer's. The locked state for unsubscribed suppliers stays as it is today |
| profile | `/supplier/account` | `supplier.account` | 🔀 | Tabs: Company, Coverage, Documents. **Read-only**. Editing, gallery, public toggle and "Preview as buyer" are skipped |
| settings | merged into `/supplier/account` | none | ⏭ | Only the language switch exists |
| Account banners (under review, expiring, expired) | layout | none | ✅ | Driven by `VerificationStatus` and the subscription state we already compute. The CTA goes to the right place: we fix the prototype bug where every banner linked to Subscription |
| Sidebar plan widget | layout | none | ✅ | Status badge, days left, progress bar |

### 3.4 Admin (`/admin`, Filament 5)

| Design screen | Our URL | Status |
|---|---|---|
| overview | `/admin` (Dashboard, 3 widgets) | ✅ Restyled. KPIs we already compute and the revenue chart. The governorate and category breakdowns are skipped |
| queue | `/admin/supplier-profiles` (Pending tab) | ✅ |
| review | `/admin/supplier-profiles/{record}` | ✅ Approve / reject (with note) / suspend already exist. Automated checks, per-document marking and the audit log are skipped |
| suppliers / buyers | `/admin/supplier-profiles`, `/admin/users` | ✅ Buyers view = users filtered by role |
| account (360° detail) | none | ⏭ |
| rfqs | `/admin/rfqs` | ✅ |
| rfqdetail, quotes | none | ⏭ |
| subs | `/admin/subscriptions` | ✅ Extend action exists |
| cats | `/admin/categories` | ✅ Units are skipped |
| reports | `/admin/reports` | ✅ |
| mod, content, settings, admins | none | ⏭ |

The admin approach needs a decision: **Q1**.

---

## 4. Architecture of the new front end

### Blade components

These go in `resources/views/components/`. Existing components are reused and renamed only where needed.

- **Layouts**
  - `layouts/app` (base: fonts, `dir`, toast)
  - `layouts/marketing` (landing)
  - `layouts/auth` (split panel; its content changes by context: login, buyer or supplier)
  - `layouts/buyer` and `layouts/supplier`, both built on a shared `layouts/dashboard` shell. The shell has the sidebar, top bar, mobile drawer, bottom nav and banner slot.
- **UI primitives**
  - `icon`, `button` (variants: accent, primary, secondary, ghost, danger, danger-outline; sizes lg, md, sm)
  - `badge` (maps each enum case to design colors), `chip`, `countdown-chip`
  - `field`, `input`, `select`, `textarea`, `phone-input`, `dropzone`, `checkbox`, `toggle`, `segmented`
  - `card`, `kpi-card`, `tabs`, `stepper`, `modal` (Alpine), `empty-state`, `avatar`, `verified-seal`, `data-table` (becomes stacked cards on mobile), `pagination` (Laravel paginator view)
- **Domain components**
  - `rfq-card` (supplier "ledger" style), `rfq-row`, `quote-card`, `quote-row`, `anonymous-buyer`, `plan-card`, `chat-bubble`, `thread-item`

### Badge colors

The enums' `color()` methods currently return Filament color names. Those stay as they are, for Filament. A new `badge` mapping covers the web app:

| Enum | Case → design style |
|---|---|
| RfqStatus | Draft (dashed grey) · Open (blue) · Awarded (teal) · Closed (grey) · Expired (grey) · Removed (red). "Closing soon" is a countdown chip, not a status |
| QuoteStatus | Pending → "Submitted" style (blue) · Shortlisted (amber ★) · Selected (solid teal ✓) · Rejected (red) · Withdrawn (grey) · Expired (grey, struck through) |
| VerificationStatus | Pending → Under review (grey pill) · Verified (solid teal ✓) · Rejected (red) · Suspended (dark) |
| SubscriptionStatus | Trial (blue) · Active (teal) · expiring (derived; amber) · Expired (red) · Cancelled (grey) |

### Other conventions

- **Interactivity:** Alpine (bundled with Livewire) handles modals, tabs, the drawer, the stepper and the mobile nav. I'll confirm Livewire's assets load on every layout, so Alpine is available on non-Livewire pages too. No JS dependency is added.
- **i18n:** new strings go into `lang/{ar,en}/*.php`. I'll reuse the Arabic already written in `i18n*.js` wherever it fits. The prototypes' DOM-translation approach is not used.
- **RTL:** logical utilities only (`ms-`, `ps-`, `start-`, `border-s`, `rounded-ss`). Phones and prices are forced `dir="ltr"`. The logo never mirrors. Directional arrows flip with `rtl:rotate-180`.

---

## 5. Responsive strategy

The design is desktop-only, so this section defines how each layout adapts below desktop width.

| Breakpoint | Shell (buyer and supplier) |
|---|---|
| `< 768` (mobile) | Top bar holds the logo, language toggle and a menu button. The sidebar becomes an off-canvas drawer. A **bottom tab bar** carries the 5 main items (the pattern that exists today). The "New RFQ" button becomes a floating action button on buyer pages |
| `768–1023` (tablet) | Same as mobile, but content uses 2-column grids where they fit |
| `≥ 1024` (desktop) | The design as drawn: fixed 232px sidebar and 60px top bar |

Pattern rules by screen type:

- **Tables** (RFQs, quotes, invoices, recent quotes) become stacked cards below `md`, showing the same fields and badge.
- **Side panels** (`1fr 340px` grids) stack below `lg`, with the side panel placed after the main content.
- **Quote compare:** the table/cards toggle is desktop-only. Mobile always shows cards.
- **Messages:** two panes at `lg` and up. Below that, `/chats` shows the list and `/chats/{id}` shows the thread with a back button. No fixed 760px height is used; the thread fills the viewport height (`dvh`).
- **Quote drawer:** an 820px side drawer at `lg` and up. Below that it is a full-screen sheet, and the summary becomes a sticky bottom bar showing the total and the submit button.
- **Auth:** the brand panel shows at `lg` and up. Below that, a compact navy header with the logo is shown and the form goes full-width.
- **Landing:** the 1280 frame becomes a fluid `max-w-7xl` container. The hero, benefits, pricing and FAQ grids collapse to one column. The nav links move into a menu sheet.

Test widths for QA: 360, 390, 768, 1024, 1280, 1440, in both AR (RTL) and EN (LTR).

---

## 6. Implementation phases

Each phase ends with Pint and the relevant Pest tests passing.

| # | Phase | Contents |
|---|---|---|
| 0 | Foundation | Tokens in `app.css`, Inter font in `vite.config.js`, assets copied, category images exported, `x-icon`, UI primitives, badge mapping |
| 1 | Shells | Dashboard shell (sidebar, top bar, drawer, bottom nav, banner slot), auth split layout, marketing layout, toast restyle |
| 2 | Public + auth | Landing, login, register chooser, buyer and supplier steppers, verify-phone |
| 3 | Buyer | Dashboard, My RFQs, create wizard (+ Review step), RFQ detail and quote compare (table/cards, shortlist, reject and award modals), Quotes, Account/Settings |
| 4 | Supplier | (Dashboard if Q8 is approved), Browse, RFQ detail with gate states, quote form, My Quotes, Subscription & billing, Profile (read-only), banners and plan widget |
| 5 | Chat | Shared two-pane messages for both roles, bubbles, system and file messages, Request sample, locked state |
| 6 | Admin | Filament restyle according to Q1 |
| 7 | QA | RTL/LTR pass at every test width, empty states on every list (the design has almost none, so they follow the design-system "empty state" pattern), existing tests updated where copy or markup changed, full suite run |

---

## 7. Test impact

The existing tests (`SmokeTest`, `QuoteLifecycleTest`, `SupplierFeedTest`, `SubscriptionTest`, `ChatTest`, `CreateRfqTest`, `AdminPanelTest`) assert text through `assertSee` / `__()` keys, and some reference `wire:` components. I'll keep the translation keys and component names stable where possible. Wherever a test breaks only because copy moved, I'll update it and tell you which ones changed. No test will be deleted.

---

## 8. Design inconsistencies to resolve

This section lists what the prototypes disagree on and my default choice for each.

| Topic | Design says | My default |
|---|---|---|
| Featured plan | Landing: **1 year** highlighted, "SAVE 17%". Onboarding: **1 month** "Most popular" | Use the `is_best_value` flag from our `plans` table everywhere |
| Trial wording | "7-day trial after verification" vs "every plan starts with 7 free days" | Show `trial_days` from the plan row. Copy matches our actual rule: the trial applies to the first subscription only |
| Quote VAT | VAT is a % field and the total always adds it | Keep our `vat_mode` (included/excluded) + `vat_amount` |
| Quote total | 327,900 in the drawer vs 324,900 elsewhere | Use our `total_price` as stored |
| RFQ statuses | Adds "Cancelled"; we have Expired and Removed | Our enum stays. Removed uses the red "cancelled" style |
| "Not selected" quote status | Design has it | Our `SelectQuote` marks the other quotes **Rejected**. See Q11 |
| Category photos | 8 design categories | Our 7 main categories map to them: food → food, beverages → coffee, packaging → packaging, building-materials → construction, medical-supplies → medical, stationery → office, textiles → no photo (cleaning or bakery?). See Q4 |
| Search/bell in the top bar | Shown on every shell | Hidden until those features exist |

---

## 8b. Build status (2026-09-21)

Everything in §3 is built except the items marked ⏭.
- **Public:** the landing page uses real data (plans, categories with admin-uploaded images, published testimonials, and a live anonymised RFQ card). Login, the `/register` chooser, the buyer (2-step) and supplier (4-step) stepper registrations, and OTP are all done.
- **Buyer:** dashboard, My RFQs with status tabs, the 4-step wizard with **Save draft** (drafts are resumable at `/buyer/rfqs/{rfq}/edit` and can be deleted), RFQ detail, quotes comparison (table/cards, award and reject modals), quotes inbox, settings, and the **supplier public profile** at `/buyer/suppliers/{supplierProfile}`.
- **Supplier:** dashboard (`/supplier`; the feed moved to `/supplier/rfqs`), browse, RFQ detail with gate states, the **quote drawer** (the `/supplier/rfqs/{rfq}/quote` deep link still works), My Quotes, subscription and billing, a read-only profile, and a secured attachment download route.
- **Chat:** two panes on desktop; list → thread on phones.
- **Admin:** Filament 5 with a custom theme (`resources/css/filament/admin/theme.css`), a dark sidebar, and KPI cards. The panel follows each admin's saved language.
- **Tests:** 192 passing.

## 9. Decisions (answered 2026-09-21)

| # | Decision |
|---|---|
| 1 | Admin: keep Filament and apply a custom Filament theme matching the design |
| 2 | After registration + OTP, users go straight to their dashboard (no "done" screen). The landing page takes `/`; a view-only `/register` role chooser links to the two registration forms |
| 3 | Landing trust-strip numbers are hidden until the numbers are real |
| 4 | Landing content comes only from real data: the categories shelf uses real main categories, with an admin-uploaded image per category. Testimonials are managed by admins (new model + Filament resource); if there are none, the section is hidden. Nothing is hard-coded |
| 5 | RFQ detail and quotes stay as two pages (both re-skinned). Merging them is deferred to UI phase 2 |
| 6 | Login keeps phone **or** email |
| 7 | Supplier public profile is built now (read-only, from existing data) |
| 8 | Supplier dashboard is added at `/supplier`. The feed moves to `/supplier/rfqs` (route name `supplier.feed` is kept) |
| 9 | RFQ wizard gets **Save draft**: the RFQ is saved with `status=draft`, resumable from My RFQs, and joins the normal flow once published |
| 10 | Quote form becomes a slide-over drawer on the RFQ detail page (full-screen sheet on mobile). The `/supplier/rfqs/{rfq}/quote` URL keeps working as a deep link |
| 11 | Quotes auto-closed by an award are labelled "Not selected" (display only) |
| 12 | Arabic stays the default language |
| 13 | Old design files move to `docs/design/` |
| — | Everywhere the design and our app disagree, follow our real data and rules |

## 10. Questions before implementation (original)

1. **Admin panel approach.** The admin design is a fully custom UI. My recommendation is to **keep Filament and apply a custom Filament theme**: dark `#111827` sidebar, navy/orange/teal palette, Inter + IBM Plex Arabic, badge colors, card styling, and the ADMIN pill. Screens stay mapped to the existing resources. The alternative is to rebuild the admin as custom Blade/Livewire. That would be close to pixel-perfect, but it is several times the work and means re-implementing tables, filters and actions. Which do you want?
2. **Landing, role chooser and done screen.** The landing page replaces today's splash at `/`. The design puts "I'm a Buyer / I'm a Supplier" on a separate auth "role" screen. OK to add a view-only `GET /register` route for it, with the landing CTAs linking there? And do you want the auth "done" confirmation screen after OTP verification, or should users land straight on their dashboard as today?
3. **Landing numbers.** The trust strip shows "1,240+ verified suppliers · 0% · EGP 48M quoted this quarter · 27 governorates". Should I compute these live from the DB (they'll be small on demo data), or hide the strip until real volume exists?
4. **Landing testimonials and category photos.** The three testimonials are invented quotes from fictional people. Should I keep them as placeholders, remove the section, or wait for real ones? For category photos, is the mapping in §8 fine, and should textiles use the cleaning or the bakery photo, or none?
5. **RFQ detail + quotes merge.** The design shows received quotes on the RFQ detail page. OK to render the existing `buyer.rfq-quotes` Livewire component inside `/buyer/rfqs/{rfq}`, with `/buyer/rfqs/{rfq}/quotes` redirecting to it? The alternative is to keep two pages.
6. **Login field.** The design logs in with **mobile + password** only. We accept phone **or** email today. Should I keep accepting both (the label becomes "Mobile number or email"), or restrict the UI to phone?
7. **Supplier public profile.** Supplier names link to a profile page in the design. We have most of the data (company, verification, rating, reviews, coverage, logo, completed deals) but no page. Build a read-only `/buyer/suppliers/{supplier}` page now, or log it as a suggestion?
8. **Supplier dashboard.** Add a supplier dashboard (KPIs from existing data)? If yes, it takes `/supplier` and the feed moves to `/supplier/rfqs`, which means a route change. If no, `/supplier` stays as Browse.
9. **Save draft in the RFQ wizard.** `RfqStatus::Draft` exists. Does the current wizard persist drafts? If it doesn't, should I hide the "Save draft" button or treat it as a suggestion?
10. **Quote form: drawer or page.** On desktop, should the quote form be a slide-over drawer on the RFQ detail page (as designed), or keep its own URL `/supplier/rfqs/{rfq}/quote` styled as a two-column page? My recommendation: keep the route and render it as the two-column form + sticky summary. That keeps tests and deep links working, and matches the mobile sheet.
11. **Rejected vs "Not selected".** When a buyer awards a quote, the other suppliers currently see **Rejected**. The design shows **Not selected**, which is kinder. Relabelling it would be a display-only change (same status, different label when `rejection_reason_id` is null). OK?
12. **Default language.** The design previews EN by default. Our app defaults to AR (RTL). Should it stay AR?
13. **Old prototype.** `TawreedHubPrototype.html` at the project root is the old mobile prototype. After the re-skin, should it (and `tawreed-new-ui/`) stay in the repo, move to `docs/`, or be deleted?
