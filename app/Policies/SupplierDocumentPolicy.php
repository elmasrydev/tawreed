<?php

namespace App\Policies;

use App\Models\SupplierDocument;
use App\Models\User;

class SupplierDocumentPolicy
{
    /**
     * Legal documents are seen only by the supplier who uploaded them and by
     * the admins who verify them. Buyers never see them.
     */
    public function view(User $user, SupplierDocument $document): bool
    {
        return $user->isAdmin()
            || $user->id === $document->supplierProfile->user_id;
    }
}
