# TawreedHub — Feature Inventory (from the mobile prototype)

Source: `docs/design/TawreedHubPrototype.html` (Claude Design bundle, 10 screens, AR/EN).

## Product summary

- **What it is:** a wholesale RFQ (request‑for‑quotation) marketplace for Egypt. Buyers post supply requests; verified suppliers submit competing quotes.
- **Business model:** zero commission. Revenue comes only from **supplier subscriptions** (15 days / 1 month / 1 year).
- **Core promise:** buyer anonymity. Suppliers see only the buyer's *business type + governorate* until the buyer selects a quote, which opens a private 1:1 chat and reveals contact details to both sides.
- **Roles:** Buyer, Supplier, Admin.
- **Locale:** Arabic (RTL, default) and English (LTR). Currency EGP. Locations are Egyptian governorates only (no street address ever requested for RFQs).

## Privacy / visibility rules (the domain core)

| Data | Other buyers | Supplier (no subscription) | Supplier (subscribed) | Selected supplier | Admin |
|---|---|---|---|---|---|
| Buyer business type + governorate | — | ✓ | ✓ | ✓ | ✓ |
| Buyer company name, contact person, phone, email, address | — | ✗ | ✗ | ✓ (after selection) | ✓ |
| RFQ title, specs, qty, unit, delivery date, deadline, attachments, notes | — | ✓ | ✓ | ✓ | ✓ |
| Number of quotes on an RFQ | — | ✓ (count only) | ✓ (count only) | ✓ | ✓ |
| Competitors' prices / identities | — | ✗ | ✗ | ✗ | ✓ |
| Supplier name + verified badge + rating on a quote | shown to the RFQ owner | — | — | — | ✓ |
| "Your quote was selected" event | — | ✓ but **locked** ("a supply company selected your quote — subscribe to unlock") | ✓ unlocked (buyer details + open chat) | ✓ | ✓ |
| Chat | only RFQ owner ↔ selected supplier | ✗ (locked) | ✓ | ✓ | read for moderation |

Expired subscription ⇒ account is **paused**, never deleted: data, quotes and history are retained and everything returns on renewal.

## Screens and features

### 1. Splash / account type
- App name, tagline, "zero commission · subscriptions only" badge.
- Choose: *I'm a Buyer* (restaurants, hotels, schools, hospitals, offices…) / *I'm a Supplier* (factories, wholesalers).
- Language toggle AR ⇄ EN (persists across the app; flips `dir`).
- Link to admin panel login.

### 2. Buyer registration
- Fields: purchasing manager name, job title, phone, email, company name, company address, commercial registration (optional), tax card (optional).
- Business type picker (single choice): Company, Restaurant/Café, Hotel, Supermarket, School/University, Hospital/Clinic, Factory (raw materials), Contractor, Catering, Office, SME.
- Privacy note explaining anonymity.
- Create account (password implied; OTP verification is mentioned for suppliers and should apply to buyers too).

### 3. Buyer app (bottom tabs)
- **Home:** greeting, "New RFQ" CTA, stat tiles (active requests, new quotes), list of my requests.
- **My Requests:** RFQ cards with image, title, subtitle, qty, deadline, quote count, status pill: *Active*, *Ending soon*, *Awarded*, *Closed*.
- **Quotes inbox:** received quotes grouped by request, "best price so far" per RFQ.
- **Chats:** list of open chats; empty state explains chats open only after selecting a quote.
- **Account:** company, business type, governorate, commercial registration status, language, logout.

### 4. Create RFQ (3‑step wizard)
- Step 1 — Product & specs: product name, main category (Food, Beverages, Packaging, Building materials, Medical supplies, Stationery…), sub‑category, detailed specifications.
- Step 2 — Quantity & delivery: quantity, unit of measure, delivery governorate (governorate only), required delivery date, supply type (*one‑time* / *recurring*).
- Step 3 — Attachments & deadline: photos/files (optional), quote submission deadline, extra notes & conditions.
- Publish ⇒ toast "RFQ published — you'll be notified of each new quote"; lands on My Requests.
- Draft/back navigation between steps; progress bar.

### 5. RFQ detail + received quotes (buyer)
- Anonymity banner ("suppliers only see Café, Cairo").
- Quote card: supplier name, avatar/logo, verified badge, rating + review count, total price, unit price, detail rows: VAT (included / amount), delivery cost, delivery date, validity, payment terms, sample availability, origin, warranty.
- Actions per quote: **Select & open chat**, **Shortlist** (toggle), **Reject** (reason picker: price too high, delivery time unsuitable, specs don't match, payment terms unsuitable, chose another supplier), **View supplier profile**.
- States: selected (highlighted, "Quote selected — open chat"), rejected (dimmed, reason shown), shortlisted.
- Selecting a quote ⇒ RFQ becomes *Awarded*, chat is created, contact details revealed.

### 6. Supplier public profile
- Name, verified badge, rating (e.g. 4.8) and review count.
- Info rows: activity, verification (comm. reg. + tax card), years active, delivery areas, completed deals.
- Portfolio images.
- Buyer reviews (anonymised reviewer: "Hotel — Giza"), star rating, text.

### 7. Chat (post‑selection, 1:1)
- Header: counterpart name, avatar, online status, link to profile, back.
- Banner: "You selected X — chat opened and contact details revealed to both sides" (supplier sees the mirror text).
- Text messages with timestamps; file messages (e.g. proforma invoice PDF).
- Quick action: **+ Request sample** (inserts a templated message).
- Composer with send; works from both buyer and supplier views.

### 8. Supplier registration
- Fields: responsible person, phone, email + password, company/factory name, commercial registration no., tax number, facility address, activity description, payment method (bank transfer…).
- Document uploads: commercial registration (PDF/photo), tax card, company logo + portfolio.
- Coverage/delivery governorates (multi‑select chips + add).
- Submit for review ("our team reviews documents within 48h"); OTP phone verification.
- Account status badge: *Verified*, *Under review*, *Rejected* (with hint, e.g. "unclear document — re‑upload"), *Suspended*.

### 9. Supplier app (bottom tabs)
- **Browse RFQs (feed):** anonymised cards — entity type + governorate, title, specs, qty, delivery date, quote deadline, "N quotes submitted"; competitor prices/identities never shown. Filter/search by category and governorate (implied).
- **Submit quote form:** unit price, total price, min order qty, VAT, transport/delivery cost, expected delivery date, quote validity, payment terms, sample availability, brand/country of origin, extra specs, warranty/replacement policy; privacy note; send ⇒ toast, lands on My Quotes.
- **My Quotes:** list with price, sent date, status: *Pending*, *Not selected*, *Selected* (+ rejected reason optional).
- **Selected‑quote card:** locked variant (no subscription) vs unlocked variant (buyer details + open chat).
- **Chats:** list with deal title, preview, time, unread count; locked screen when unsubscribed ("View subscription plans").
- **Subscription:** current plan card with dates; expiry banner ("expires in 5 days — renew now"); plans: 15 days EGP 150, 1 month EGP 250, 1 year EGP 2,500 (best value, 7‑day free trial for new members); auto‑renew toggle; invoices & e‑receipts list (INV‑2026‑0413, PDF); paused‑not‑deleted note; "Current plan" marker.
- **Account:** comm. registration, tax number, coverage, subscription status, verification status + hint, language, logout.

### 10. Admin dashboard
- **Overview:** KPIs (active RFQs, quotes submitted, active subscriptions, monthly revenue) with weekly deltas; weekly subscription revenue bar chart; **supplier verification queue** (name, activity, governorate, comm. reg. + tax card docs; approve / reject / suspend); management shortcuts (subscriptions, users, categories, reported content) with counts.
- **Subscriptions:** stats (active, expiring in 7 days, expired/paused); list with supplier, plan, dates, status; actions: extend plan, view invoice.
- **Users:** search by name / phone / commercial reg.; list with role and governorate; suspend / unsuspend. Categories with RFQ counts; add category.
- **Reports:** reported content (non‑compliant quote, duplicate RFQ, inappropriate chat message) with target, reason, time; actions: remove content (+ notify user) / dismiss.

## Cross‑cutting
- Bilingual AR/EN with full RTL/LTR layout switch; Arabic‑capable font (IBM Plex Sans Arabic).
- Toast feedback after every action.
- Notifications: new quote on my RFQ (buyer), quote selected (supplier), verification decision (supplier), subscription expiring (supplier), report resolved.
- OTP phone verification at registration.
- Electronic invoices/receipts (PDF) for subscription payments.
- Payment gateway is stubbed in the prototype ("payment disabled").
- Moderation: flag/report content; auto‑detect contact details inside quotes (rule from report r1).
