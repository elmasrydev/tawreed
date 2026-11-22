<?php

namespace App\Http\Controllers\Buyer;

use App\Actions\Buyer\UpdateBuyerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\UpdateBuyerProfileRequest;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function update(UpdateBuyerProfileRequest $request, UpdateBuyerProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle($request->user(), $request->validated());

        return redirect()
            ->route('buyer.account')
            ->with('status', __('buyer.profile_saved'));
    }
}
