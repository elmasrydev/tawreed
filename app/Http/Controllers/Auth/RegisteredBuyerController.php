<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterBuyer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\BuyerRegistrationRequest;
use App\Models\BusinessType;
use App\Models\Governorate;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredBuyerController extends Controller
{
    public function create(): View
    {
        return view('auth.register-buyer', [
            'businessTypes' => BusinessType::query()->active()->get(),
            'governorates' => Governorate::query()->orderBy('sort')->get(),
        ]);
    }

    public function store(BuyerRegistrationRequest $request, RegisterBuyer $registerBuyer, OtpService $otp): RedirectResponse
    {
        $user = $registerBuyer->handle($request->validated());

        Auth::login($user);

        if (! $otp->isEnabled()) {
            return redirect()->route($user->role->homeRoute());
        }

        return redirect()->route('email.verify')->with('status', __('otp.sent'));
    }
}
