<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterSupplier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SupplierRegistrationRequest;
use App\Models\Category;
use App\Models\Governorate;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredSupplierController extends Controller
{
    public function create(): View
    {
        return view('auth.register-supplier', [
            'governorates' => Governorate::query()->orderBy('sort')->get(),
            'categories' => Category::query()->main()->get(),
        ]);
    }

    public function store(SupplierRegistrationRequest $request, RegisterSupplier $registerSupplier, OtpService $otp): RedirectResponse
    {
        $user = $registerSupplier->handle(
            $request->safe()->except('documents'),
            array_filter($request->file('documents', [])),
        );

        Auth::login($user);

        if (! $otp->isEnabled()) {
            return redirect()->route($user->role->homeRoute());
        }

        return redirect()->route('email.verify')->with('status', __('otp.sent'));
    }
}
