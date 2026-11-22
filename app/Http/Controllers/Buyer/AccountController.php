<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use App\Models\Governorate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $isEditing = $request->boolean('edit');

        return view('buyer.account', [
            'profile' => $request->user()->buyerProfile->load(['businessType', 'governorate']),
            'isEditing' => $isEditing,
            'businessTypes' => $isEditing ? BusinessType::query()->active()->get() : collect(),
            'governorates' => $isEditing ? Governorate::query()->orderBy('sort')->get() : collect(),
        ]);
    }
}
