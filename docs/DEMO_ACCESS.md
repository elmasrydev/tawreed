# TawreedHub — Demo Access

## How to sign in

Go to `http://tawreed.test/login` and enter two things:

| Field | What to type |
|---|---|
| البريد الإلكتروني أو رقم الهاتف | The **email** from the tables below, for example `buyer@tawreedhub.test` |
| كلمة المرور | `password` |

> **The password is the word `password` for every account, including the admin.**
> The phone number listed beside each account is an *alternative username* you may type instead of the email. It is never the password. Entering the phone number in the password box gives "بيانات الدخول غير صحيحة".

The admin dashboard is a separate door: `http://tawreed.test/admin`, same email and password.

The interface is Arabic by default. Use the **EN | English** button in the header to switch language at any time; the choice is saved on the account.

## Admin

| Email (username) | Phone (alternative username) | Password | What to look at |
|---|---|---|---|
| `admin@tawreedhub.test` | 01000000000 | `password` | Dashboard KPIs, revenue chart, supplier verification queue, users, requests, subscriptions, reports, categories |

The sidebar shows two live badges: one supplier waiting for verification, and two open reports.

## Buyers

| Company | Email (username) | Phone (alternative username) | Password | Why this account |
|---|---|---|---|---|
| كافيه سيتي (Café) | `buyer@tawreedhub.test` | 01023456789 | `password` | The richest account. Four requests, competing quotes to compare, an awarded deal with an open chat |
| فندق النخيل (Hotel) | `hotel@tawreedhub.test` | 01023456790 | `password` | Hotel linen and cooking oil requests, one deadline closing tomorrow |
| مدرسة النور الدولية (School) | `school@tawreedhub.test` | 01023456791 | `password` | Stationery requests with quotes |
| مستشفى الشفاء (Hospital) | `hospital@tawreedhub.test` | 01023456792 | `password` | Medical supplies, plus an expired request |
| مقاولون المستقبل (Contractor) | `contractor@tawreedhub.test` | 01023456793 | `password` | Construction materials, including a saved draft that was never published |

## Suppliers

Each supplier is deliberately in a different state, so every screen variant can be seen without editing data.

| Company | Email (username) | Phone (alternative username) | Password | State |
|---|---|---|---|---|
| الدلتا لتحميص البن | `supplier@tawreedhub.test` | 01187654321 | `password` | Verified, monthly plan active, auto-renew on. The normal happy path |
| مطاحن النيل | `nile@tawreedhub.test` | 01187654322 | `password` | Verified, annual plan. Won the sugar deal, so has an open chat |
| النور لتوريد الأغذية | `nour@tawreedhub.test` | 01187654324 | `password` | Verified, **plan ends in 5 days** — shows the renewal banner |
| الإسكندرية للتوريدات | `alex@tawreedhub.test` | 01187654323 | `password` | Verified but **subscription expired** — account paused, chats locked, quotes and history intact |
| مصنع المحلة للنسيج | `mahalla@tawreedhub.test` | 01187654325 | `password` | **Awaiting verification** — can browse requests but cannot quote |
| الصعيد للمواد الطبية | `saeed@tawreedhub.test` | 01187654326 | `password` | **Rejected** verification, with the reviewer's note shown on the account |

## A five-minute walkthrough

1. **See the buyer's side.** Sign in as `buyer@tawreedhub.test`. The home page shows active requests and how many quotes have arrived.
2. **Compare quotes.** Open *العروض* (Quotes), then *مقارنة العروض* on the espresso beans request. Three suppliers compete. One is shortlisted, one is rejected with a reason, and the cheapest carries a "best offer" badge. Every quote shows VAT, delivery, payment terms, sample availability and origin.
3. **Watch anonymity work.** In a private window, sign in as `supplier@tawreedhub.test` and open the same request from *طلبات التوريد*. The buyer appears only as "مقهى — القاهرة". No company name, no contact details, and no competitor prices anywhere.
4. **See what selection unlocks.** Back as the buyer, open *المحادثات*. The awarded sugar deal has a live conversation with مطاحن النيل, and both sides' phone and email are now visible. That chat exists only because the buyer selected a quote.
5. **See the subscription gate.** Sign in as `alex@tawreedhub.test`, whose plan has expired, and click *المحادثات*. The app redirects to the plans page rather than showing the chat. The supplier's quotes and history are untouched.
6. **Approve a supplier.** Sign in to `/admin`. The dashboard shows مصنع المحلة للنسيج waiting for review. Approve it, then sign in as `mahalla@tawreedhub.test` and note that quoting is now allowed.

## What is in the dataset

| | |
|---|---|
| Buyers | 5 |
| Suppliers | 6, covering verified, pending, rejected and lapsed states |
| Supply requests | 13, spanning open, ending soon, awarded, expired and draft |
| Quotes | 14, spanning pending, shortlisted, selected and rejected |
| Awarded deals with chat | 1, with a six-message conversation |
| Reviews | 3 |
| Subscriptions | 4, including one expiring and one expired |
| Invoices | 7, each downloadable as PDF |
| Moderation reports | 3, two of them open |

## Rebuilding the data

```bash
php artisan migrate:fresh --seed
```

That reloads reference data (governorates, categories, units, plans) and the full demo dataset. To load reference data only, set `DEMO_SEED=false` in `.env` first.

## Things that are deliberately simulated

- **Payments.** Subscribing succeeds immediately against a fake gateway and issues a real invoice. No card is charged. Swapping in Paymob or Fawry means implementing one interface.
- **SMS.** Phone verification codes are written to `storage/logs/laravel.log` instead of being texted.
- **Chat delivery.** Messages refresh by polling every few seconds rather than over a websocket.
