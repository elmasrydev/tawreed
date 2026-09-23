# TawreedHub

A zero-commission wholesale RFQ marketplace for Egypt. Buyers post anonymous supply
requests; verified suppliers compete with quotes; revenue comes only from supplier
subscriptions.

Buyer anonymity is the core rule: a supplier sees only the buyer's business type and
governorate until the buyer selects their quote, which opens a private chat and reveals
contact details to both sides.

- `docs/FEATURES.md` — the feature inventory, screen by screen, with the visibility matrix.
- `docs/IMPLEMENTATION_PLAN.md` — architecture, data model, phased plan.
- `docs/design/TawreedHubPrototype.html` — the original mobile prototype this was first built from.
- `docs/design/tawreed-new-ui/` — the current UI design prototypes (see `docs/NEW_UI_IMPLEMENTATION_PLAN.md`).

## Stack

PHP 8.4 · Laravel 13 · Livewire 4 · Filament 5 · Tailwind 4 · MySQL 9, served by Laravel Herd.

## Local setup

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate
mysql -u root -e "CREATE DATABASE tawreed CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate --seed
npm run build
```

The app is served at http://tawreed.test by Herd. The admin panel is at `/admin`.

Rebuild assets after changing Blade views: Tailwind only emits the utility classes it
finds at build time, so a new class in a new view needs `npm run build` (or `npm run dev`).

### Seeded accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@tawreedhub.test` | `password` |
| Buyer | `buyer@tawreedhub.test` | `password` |

Sign in with either an email address or an Egyptian mobile number.

### Verification codes

There is no SMS provider. `App\Services\Sms\LogSmsSender` writes the one-time code to
`storage/logs/laravel.log`; read it from there to complete phone verification. Binding a
real gateway means swapping the `App\Contracts\SmsSender` implementation.

Setting `DEV_LOGIN_AS_ENABLED=true` in a local `.env` adds `/dev/login-as/{email}` for
signing in as a seeded account without typing credentials. Keep it off everywhere else.

## Tests

```sh
php artisan test
```

Tests run against a separate `tawreed_testing` MySQL database, created with:

```sh
mysql -u root -e "CREATE DATABASE tawreed_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

MySQL rather than SQLite, because the schema adds a deferred foreign key that SQLite
cannot apply to an existing table.

## Conventions

- Anonymisation lives in policies and view models, never in Blade templates.
- Reference tables (categories, governorates, units) store `name_ar` and `name_en`
  columns because admins add rows at runtime; everything else is translated through
  `lang/{ar,en}`.
- Run `vendor/bin/pint` before committing.
