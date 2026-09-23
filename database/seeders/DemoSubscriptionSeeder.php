<?php

namespace Database\Seeders;

use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Subscriptions covering each billing state: a healthy plan, one about to
 * lapse, a trial, and an expired plan whose supplier is paused but intact.
 */
class DemoSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->subscriptions() as $spec) {
            $supplier = User::where('email', $spec['email'])->first();
            $plan = Plan::where('slug', $spec['plan'])->first();

            if ($supplier === null || $plan === null) {
                continue;
            }

            $subscription = Subscription::updateOrCreate(
                ['supplier_id' => $supplier->id, 'plan_id' => $plan->id],
                [
                    'starts_at' => now()->addDays($spec['starts_in']),
                    'ends_at' => now()->addDays($spec['ends_in']),
                    'status' => $spec['status'],
                    'auto_renew' => $spec['auto_renew'],
                    'amount_egp' => $spec['status'] === SubscriptionStatus::Trial ? 0 : $plan->price_egp,
                    'payment_ref' => 'DEMO-'.strtoupper(substr(md5($spec['email']), 0, 10)),
                ],
            );

            $this->issueInvoices($subscription, $spec['invoices']);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function subscriptions(): array
    {
        return [
            // Healthy monthly plan with auto-renew on.
            [
                'email' => 'supplier@tawreedhub.test', 'plan' => 'monthly',
                'starts_in' => -12, 'ends_in' => 18,
                'status' => SubscriptionStatus::Active, 'auto_renew' => true, 'invoices' => 3,
            ],
            // Annual plan, comfortably active.
            [
                'email' => 'nile@tawreedhub.test', 'plan' => 'yearly',
                'starts_in' => -95, 'ends_in' => 270,
                'status' => SubscriptionStatus::Active, 'auto_renew' => false, 'invoices' => 1,
            ],
            // Ends in five days, so the renewal banner is visible.
            [
                'email' => 'nour@tawreedhub.test', 'plan' => 'monthly',
                'starts_in' => -25, 'ends_in' => 5,
                'status' => SubscriptionStatus::Active, 'auto_renew' => false, 'invoices' => 2,
            ],
            // Lapsed: the account is paused but every record is retained.
            [
                'email' => 'alex@tawreedhub.test', 'plan' => 'trial-15-days',
                'starts_in' => -40, 'ends_in' => -8,
                'status' => SubscriptionStatus::Expired, 'auto_renew' => false, 'invoices' => 1,
            ],
        ];
    }

    private function issueInvoices(Subscription $subscription, int $count): void
    {
        if ($subscription->invoices()->exists()) {
            return;
        }

        $net = (float) $subscription->amount_egp;
        $vat = round($net * 0.14, 2);

        foreach (range(1, $count) as $index) {
            Invoice::create([
                'subscription_id' => $subscription->id,
                'supplier_id' => $subscription->supplier_id,
                'number' => Invoice::nextNumber(),
                'amount_egp' => $net,
                'vat_egp' => $vat,
                'total_egp' => $net + $vat,
                'issued_at' => now()->subDays(($count - $index) * 30 + 2),
            ]);
        }
    }
}
