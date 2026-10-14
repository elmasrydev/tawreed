<?php

namespace App\Policies;

use App\Models\PortfolioItem;
use App\Models\User;

class PortfolioItemPolicy
{
    /**
     * Suppliers manage only their own portfolio.
     */
    public function update(User $user, PortfolioItem $portfolioItem): bool
    {
        return $user->id === $portfolioItem->supplierProfile->user_id;
    }

    public function delete(User $user, PortfolioItem $portfolioItem): bool
    {
        return $this->update($user, $portfolioItem);
    }
}
