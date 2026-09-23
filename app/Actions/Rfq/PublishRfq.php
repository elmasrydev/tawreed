<?php

namespace App\Actions\Rfq;

use App\Enums\RfqStatus;
use App\Events\RfqPublished;
use App\Models\Rfq;
use Illuminate\Support\Facades\DB;

class PublishRfq
{
    /**
     * Moves a draft request into the supplier feed and notifies suppliers whose
     * categories and coverage match.
     */
    public function handle(Rfq $rfq): Rfq
    {
        DB::transaction(function () use ($rfq): void {
            $rfq->forceFill([
                'status' => RfqStatus::Open,
                'published_at' => now(),
            ])->save();
        });

        RfqPublished::dispatch($rfq);

        return $rfq->refresh();
    }
}
