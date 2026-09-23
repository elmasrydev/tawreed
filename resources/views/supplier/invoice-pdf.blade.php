<!DOCTYPE html>
<html dir="{{ App\Http\Middleware\SetLocale::isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #0B3D5C; }
        .brand-bar { height: 4px; background: #0B3D5C; margin-bottom: 18px; }
        .muted { color: #6B7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border-bottom: 1px solid #E5E7EB; padding: 8px; text-align: start; }
        th { background: #F9FAFB; color: #6B7280; font-size: 11px; text-transform: uppercase; }
        .total td { font-weight: bold; color: #0B3D5C; border-bottom: none; border-top: 2px solid #0B3D5C; }
        .paid { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #D9F2EF; color: #0F7A72; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="brand-bar"></div>
    <h1>{{ __('ui.app_name') }}</h1>
    <p class="muted">{{ __('subscription.invoice_for') }}: {{ $invoice->supplier->supplierProfile?->company_name }}</p>

    <p>
        <strong>{{ $invoice->number }}</strong> <span class="paid">{{ __('subscription.paid') }}</span><br>
        <span class="muted">{{ __('subscription.issued_at') }}: {{ $invoice->issued_at->translatedFormat('j M Y') }}</span>
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('subscription.title') }}</th>
                <th>{{ __('ui.egp') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->subscription->plan->name }}</td>
                <td>{{ number_format((float) $invoice->amount_egp, 2) }}</td>
            </tr>
            <tr>
                <td>{{ __('subscription.vat') }}</td>
                <td>{{ number_format((float) $invoice->vat_egp, 2) }}</td>
            </tr>
            <tr class="total">
                <td>{{ __('subscription.total') }}</td>
                <td>{{ number_format((float) $invoice->total_egp, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
