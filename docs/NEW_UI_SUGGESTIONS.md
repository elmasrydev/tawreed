# TawreedHub: Suggestions from the new UI (not implemented yet)

These features appear in the `docs/design/tawreed-new-ui/` prototypes, but the application does not support them today. They are **left out** of the UI re-skin (see [`NEW_UI_IMPLEMENTATION_PLAN.md`](NEW_UI_IMPLEMENTATION_PLAN.md)) and kept here for later. Each entry names the design file and screen it comes from.

**Effort:** S is up to 1 day, M is 2–4 days, L is 1 week or more.

---

## 1. Cross-cutting

| # | Feature | Where in design | Notes | Effort |
|---|---|---|---|---|
| 1.1 | In-app notification centre (bell dropdown, unread dot, mark all read) | Buyer / Supplier / Admin top bar | The `notifications` table exists, but no notifications are sent yet (FEATURES.md, phase 8). Events: new quote, quote expiring, clarification asked, deadline reached, quote selected, verification decision, subscription expiring | M |
| 1.2 | Per-event, per-channel notification preferences (in-app / email / SMS), daily digest, weekly summary | Buyer Settings › Notifications; Supplier Settings › Notifications | Depends on 1.1 | M |
| 1.3 | Global search (RFQs, suppliers, quotes, invoices) | Top bar of every app | | M |
| 1.4 | Password reset: phone → email link, with a "team will call you" fallback | Auth › forgot | Laravel's password broker plus an email template. A reset-by-SMS-OTP variant is also possible | S |
| 1.5 | Change password from settings | Buyer / Supplier Settings › Account | | S |
| 1.6 | Account deletion (open RFQs cancelled, suppliers notified) | Buyer / Supplier Settings › Danger zone | | M |
| 1.7 | SMS two-factor authentication on new devices | Supplier Settings › Account & security | We already have `OtpService` | M |
| 1.8 | Language preference that also drives emails and SMS, plus an Arabic-numerals toggle | Supplier Settings › Language | `users.locale` exists; the numerals option is new | S |
| 1.9 | Real-time updates (quote received, quote selected, new message) | Supplier "Your quote was selected" celebration modal | Reverb + Echo; chat currently polls | M |

## 2. Public site and onboarding

| # | Feature | Where in design | Notes | Effort |
|---|---|---|---|---|
| 2.1 | Legal and company pages (Terms, Privacy, Supplier rules, Refund policy, About, Careers, Blog, Contact) | Landing footer, Auth footer | The footer links point to `#` in the design | S–M |
| 2.2 | ~~Real testimonials~~ | Landing › Testimonials | **Done:** admins manage testimonials in Filament (Website › Testimonials). The section hides until one is published | — |
| 2.3 | "All categories" page with open-RFQ counts | Landing › Categories | | S |
| 2.4 | Supplier: company name in both EN **and** AR | Auth › sup-company | Currently a single `company_name` | S |
| 2.5 | Supplier type (Manufacturer, Farm, Importer, Distributor, Wholesaler, Equipment) | Auth › sup-company, Admin queue | New column or enum | S |
| 2.6 | Supplier HQ governorate, separate from coverage | Auth › sup-company | | S |
| 2.7 | Extra verification documents: tax registration certificate (required) and facility photos (optional, up to 3) | Auth › sup-docs, Supplier Profile › Documents | Adds cases to `SupplierDocumentType` | S |
| 2.8 | Cap of 5 supplier categories | Auth › sup-cats | Validation rule | S |
| 2.9 | Choose plan + payment method (Fawry, wallet, card, bank transfer) during signup, "Due today EGP 0" | Auth › sup-plan | Needs a real payment gateway (4.x) | L |
| 2.10 | Buyer "Individual" account type, with an RFQ value cap (EGP 500,000) until company docs are added | Auth › buy-biz, Admin settings | | M |
| 2.11 | Buyer delivery city/area in addition to governorate | Auth › buy-biz, Buyer create RFQ step 2 | Governorate → city lookup table | M |
| 2.12 | Buyer interests step (preferred categories, purchase frequency, notify toggle), used to pre-fill the first RFQ | Auth › buy-prefs | | S |
| 2.13 | Onboarding "done" screen with progress checklist | Auth › done | Declined for now: users go straight to their dashboard after OTP | S |

## 3. Buyer app

| # | Feature | Where in design | Notes | Effort |
|---|---|---|---|---|
| 3.1 | Supplier public profile: the remaining parts (cover photo, review tags, extra stats) | Buyer › supplier | **Base page done** at `/buyer/suppliers/{id}` from existing data. The design extras are still open | S |
| 3.2 | Supplier directory with filters (category, governorate, rating 4+, verified only) | Buyer › suppliers | | M |
| 3.3 | Supplier stats: quotes won, average response time, on-time delivery %, repeat buyers | Buyer › supplier; Design System supplier card | Needs tracking of response time and delivery outcome | M |
| 3.4 | Review tags (On-time, Quality, Communication) | Buyer › supplier reviews | Pivot on `reviews` | S |
| 3.5 | RFQ actions: **Duplicate**, **Close early**, **Cancel** | Buyer › detail header | Cancel would add a status | M |
| 3.6 | **Extend deadline** (for RFQs with zero quotes) | Buyer › dash "Needs attention" | | S |
| 3.7 | Recurring RFQs as structured data (frequency × duration, e.g. weekly for 6 months) | Buyer › create step 2 | Today there is `supply_type` + a free-text `recurrence_note` | M |
| 3.8 | Rich-text specifications editor | Buyer › create step 1 | | S |
| 3.9 | Detailed delivery address on the RFQ (revealed only after award) | Buyer › create step 2 | | S |
| 3.10 | Quote-deadline quick picks (24h, 2 days, 5 days, 1 week) | Buyer › create step 3 | UI only. It could be added during the re-skin if wanted | S |
| 3.11 | "Ask suppliers to specify" required quote fields per RFQ (brand/origin, sample, MOQ, warranty, certificates) | Buyer › create step 3; Supplier detail "Please specify" | | M |
| 3.12 | Side-by-side **compare** view for shortlisted quotes | Buyer › detail "Compare selected" | Not designed yet | M |
| 3.13 | ~~"Lowest price" / "Fastest delivery" ribbons~~ | Buyer › detail | **Done** on the buyer quotes page | — |
| 3.14 | Quote validity countdown ("Expires in 1d 4h") and a job that expires quotes | Buyer › dash, quotes | `validity_days` exists; no expiry job yet | S |
| 3.15 | Privacy settings: per-field reveal on award (contact name, phone, email, address) | Buyer › settings › Privacy, Award modal "Change privacy settings" | | M |
| 3.16 | Buyer organisation editing plus KYC documents (commercial register, tax card upload) | Buyer › settings › Organisation | | M |
| 3.17 | Chat quick actions **Arrange trial session** and **Mark deal done** (which unlocks the review) | Buyer / Supplier › messages | "Request sample" already exists | M |
| 3.18 | ~~Chat read receipts (✓✓) and unread counters in the nav~~ | Messages, sidebar badges | **Done:** ✓✓ on read messages, unread pills in the thread list and nav badges | — |
| 3.19 | Report a user from the chat | Buyer › settings › Privacy note | | S |
| 3.20 | ~~Dashboard "Awarded this month"~~ | Buyer › dash KPI | **Done:** count plus the total of the selected quotes | — |

## 4. Supplier app

| # | Feature | Where in design | Notes | Effort |
|---|---|---|---|---|
| 4.1 | Supplier analytics: win rate vs last month, EGP won, per-category win rate, last winning price | Supplier › dash, detail "Competition" card | | M |
| 4.2 | Saved/bookmarked RFQs | Supplier › browse | | S |
| 4.3 | Advanced feed filters with counts (quantity range, delivery date, recurring only, closing < 24h) and sort (closing soon, newest, largest qty) | Supplier › browse | | M |
| 4.4 | "New today" badge in the sidebar | Supplier › sidebar | The dashboard KPI already shows "N new today"; only the sidebar badge is open | S |
| 4.5 | Clarification Q&A between supplier and anonymous buyer before award | Supplier › detail "Ask a clarification"; Buyer notifications | New entity plus moderation | L |
| 4.6 | Buyer history on the anonymous card ("12 RFQs · 4 awarded") | Supplier › detail | | S |
| 4.7 | **Edit** and **Withdraw** a quote while the RFQ is open | Supplier › quotes | `QuoteStatus::Withdrawn` exists, but no action | M |
| 4.8 | Quote attachments (certificates) | Supplier › quote drawer | Media collection on `Quote` | S |
| 4.9 | Quote "Sample: yes free / yes paid / no" as a structured choice | Supplier › quote drawer | Today `sample_availability` is text | S |
| 4.10 | Profile editing (company info, activity description, coverage, accepted payment methods) | Supplier › profile | | M |
| 4.11 | Portfolio gallery upload (max 12) | Supplier › profile › Gallery | The `portfolio` document type exists; upload UI doesn't | S |
| 4.12 | Public-profile visibility toggle and "Preview as buyer" | Supplier › profile | Depends on 3.1 | S |
| 4.13 | Document expiry tracking and re-upload triggering re-review | Supplier › profile › Documents | | M |
| 4.14 | Team members with roles (Owner, Quoting) and invitations | Supplier › settings › Account & security | | L |
| 4.15 | Saved payment method (card on file), mobile wallet, Fawry | Supplier › sub › Payment method | Needs a real gateway (Paymob/Fawry) | L |
| 4.16 | Plan change applied at next renewal | Supplier › sub › Change plan | | S |
| 4.17 | Renewal reminders (7, 3, 1 days, by email + SMS) configuration | Supplier › sub | The scheduled command from the original plan may partly exist | S |
| 4.18 | Failed-payment records on invoices | Supplier › sub › Invoices | | S |
| 4.19 | Grace period after expiry | Admin › settings › Payments | | S |

## 5. Admin

| # | Feature | Where in design | Notes | Effort |
|---|---|---|---|---|
| 5.1 | Overview extras: 7d/30d/90d range, RFQs and quotes per day chart, revenue by plan + MRR, top categories, activity by governorate (+ future map) | Admin › overview | | M |
| 5.2 | Verification SLA (waiting hours, over-SLA highlighting), "More docs" and "Resubmitted" statuses, **Request more docs** action | Admin › queue, review | | M |
| 5.3 | Per-document marking (Valid / Unreadable), document viewer with zoom and rotate | Admin › review | | M |
| 5.4 | Automated checks: tax-number format, name vs register, duplicate account, banned-phone list; "ban phone" when documents are forged | Admin › review | | M |
| 5.5 | Audit log and internal admin notes per supplier, account and RFQ; settings change log | Admin › review, account, rfqdetail, settings | e.g. `spatie/laravel-activitylog` (needs approval as a dependency) | M |
| 5.6 | Account 360° page: activity tabs, trust signals, per-account metrics vs platform average | Admin › account | | M |
| 5.7 | **Impersonate** user | Admin › account | | S |
| 5.8 | Admin → user messaging | Admin › account, rfqdetail | | M |
| 5.9 | CSV export of users; admin-created accounts | Admin › suppliers/buyers | | S |
| 5.10 | RFQ detail for admins (quotes with lowest/median price, timeline), **Hide from suppliers** / Restore | Admin › rfqdetail | | M |
| 5.11 | RFQ auto-flagging rules (off-platform contact, duplicate RFQ) | Admin › rfqs | `ContactDetailDetector` exists for quotes and could be reused | M |
| 5.12 | Quotes monitor with price-anomaly flag (deviation from median), clear or remove, supplier warning | Admin › quotes | | M |
| 5.13 | Payments ledger: methods, refunds, retry, manual bank-transfer confirmation | Admin › subs | Needs 4.15 | L |
| 5.14 | Plan editing (price, trial) from admin | Admin › subs "Edit plan" | A Filament `PlanResource` would be quick | S |
| 5.15 | Category drag-and-drop ordering and sub-category enable toggle; **units of measure** CRUD | Admin › cats | `sort`/`is_active` exist; a units resource is new | S |
| 5.16 | Reports: evidence attachments, review disputes (remove/edit/keep + rating recalculation), warn/strike, request info with SLA pause | Admin › reports | | M |
| 5.17 | **Chat message moderation**: rule engine (OFFPLATFORM_CONTACT, PAYMENT_REDIRECT, ABUSE, SPAM_LINK), hold queue, allow/block, strike escalation (warn → 7-day restriction → suspend), false-positive stats | Admin › mod | | L |
| 5.18 | **CMS**: bilingual pages with draft/published and translation status, FAQ management with view counts, scheduled audience-targeted announcement banner | Admin › content | | L |
| 5.19 | **Platform settings** store: min RFQ deadline, max quotes per RFQ, individual buyers, verification SLA, trial length, grace period, payment-method toggles, SMS quota, default language | Admin › settings | | M |
| 5.20 | **Admin users and RBAC**: roles Super / Ops / Finance / Support, permission matrix, mandatory 2FA, invite by email (72h expiry) | Admin › admins | e.g. Filament Shield or `spatie/laravel-permission` (needs approval as a dependency) | L |

## 6. Found during implementation

| # | Feature | Where in design | Notes | Effort |
|---|---|---|---|---|
| 6.1 | Terms-of-service checkbox and preferred-language choice at signup | Auth › account | Needs the legal pages from 2.1 first | S |
| 6.2 | RFQ quote deadline with a time of day ("18 Sep · 17:00") | Buyer › create step 3 | `quote_deadline` is a date today, and quoting stops at the start of that day | S |
| 6.3 | Multiple accepted payment methods per supplier | Supplier › profile, Buyer › supplier profile | `payment_method` is a single text field today | S |
| 6.4 | Live-refreshing chat thread list (new conversations and unread counts without reload) | Messages | Only the open thread polls today | S |
| 6.5 | Business rule to confirm: should quoting require an active subscription? | Supplier › detail gate | Today any verified supplier can quote. A plan unlocks buyer details and chat after selection. The design's "expired = read-only, can't quote" wording implies a stricter rule | S |
| 6.6 | Quote "Not selected" as its own status | Buyer / Supplier quotes | Shown today as a label on Rejected quotes that have no reason | S |
| 6.7 | Buyer cancels / closes an open RFQ; supplier sees "Cancelled" | Buyer › detail | Related to 3.5. Our enum has Closed/Expired/Removed but no buyer-driven cancel | M |

