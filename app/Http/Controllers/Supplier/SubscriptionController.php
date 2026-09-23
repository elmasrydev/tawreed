<?php

namespace App\Http\Controllers\Supplier;

use App\Actions\Subscription\SubscribeToPlan;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Plan;
use App\Support\ShellState;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    public function show(Request $request): View
    {
        $supplier = $request->user();

        $account = ShellState::supplierAccount($supplier);

        return view('supplier.subscription', [
            'plans' => Plan::query()->active()->orderBy('sort')->get(),
            'subscription' => $account['subscription'],
            'account' => $account,
            'lastSubscription' => $account['subscription'] ? null : $supplier->subscriptions()->with('plan')->latest('ends_at')->first(),
            'isFirstSubscription' => ! $supplier->subscriptions()->exists(),
            'invoices' => $supplier->invoices()->with('subscription.plan')->latest('issued_at')->take(12)->get(),
        ]);
    }

    public function store(Request $request, Plan $plan, SubscribeToPlan $subscribeToPlan): RedirectResponse
    {
        abort_unless($plan->is_active, 404);

        try {
            $subscribeToPlan->handle($request->user(), $plan);
        } catch (PaymentFailedException) {
            return back()->with('status', __('subscription.payment_failed'));
        }

        return redirect()
            ->route('supplier.subscription')
            ->with('status', __('subscription.activated'));
    }

    public function toggleAutoRenew(Request $request): RedirectResponse
    {
        $subscription = $request->user()->activeSubscription();

        abort_if($subscription === null, 404);

        $subscription->update(['auto_renew' => ! $subscription->auto_renew]);

        return back()->with('status', __('subscription.auto_renew_updated'));
    }

    /**
     * Renders the electronic invoice the prototype offers as a PDF download.
     */
    public function invoice(Request $request, Invoice $invoice): Response
    {
        abort_unless($invoice->supplier_id === $request->user()->id, 403);

        $invoice->load(['subscription.plan', 'supplier.supplierProfile']);

        return Pdf::loadView('supplier.invoice-pdf', ['invoice' => $invoice])
            ->download("{$invoice->number}.pdf");
    }
}
