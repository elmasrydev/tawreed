<?php

namespace App\Policies;

use App\Models\Rfq;
use App\Models\User;

class RfqPolicy
{
    /**
     * Only the buyer who posted a request may open its detail screen, where
     * quotes and supplier identities are visible.
     */
    public function view(User $user, Rfq $rfq): bool
    {
        return $user->id === $rfq->buyer_id || $user->isAdmin();
    }

    /**
     * Suppliers browse the anonymised feed; the buyer's identity is stripped by
     * the view model, never by the template.
     */
    public function browse(User $user, Rfq $rfq): bool
    {
        if ($user->id === $rfq->buyer_id || $user->isAdmin()) {
            return true;
        }

        return $user->isSupplier() && $rfq->isOpen();
    }

    public function create(User $user): bool
    {
        return $user->isBuyer() && $user->hasVerifiedPhone();
    }

    public function update(User $user, Rfq $rfq): bool
    {
        return $user->id === $rfq->buyer_id && $rfq->isOpen();
    }

    /**
     * A buyer resumes only their own unpublished drafts in the wizard.
     */
    public function editDraft(User $user, Rfq $rfq): bool
    {
        return $user->id === $rfq->buyer_id && $rfq->isDraft();
    }

    /**
     * Drafts were never seen by suppliers, so their owner may discard them.
     */
    public function deleteDraft(User $user, Rfq $rfq): bool
    {
        return $this->editDraft($user, $rfq);
    }

    /**
     * Verified suppliers may quote an open request they have not quoted before.
     */
    public function quote(User $user, Rfq $rfq): bool
    {
        if (! $user->isSupplier() || ! $user->hasVerifiedPhone()) {
            return false;
        }

        if (! $user->supplierProfile?->canSubmitQuotes()) {
            return false;
        }

        if (! $rfq->acceptsQuotes()) {
            return false;
        }

        return ! $rfq->quotes()->where('supplier_id', $user->id)->exists();
    }
}
