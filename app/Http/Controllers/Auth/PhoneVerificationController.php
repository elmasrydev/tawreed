<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhoneVerificationController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedPhone()) {
            return redirect()->route($request->user()->role->homeRoute());
        }

        return view('auth.verify-phone');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:'.OtpService::CODE_LENGTH],
        ]);

        if (! $this->otp->verify($request->user(), $validated['code'])) {
            return back()->withErrors(['code' => __('otp.invalid')]);
        }

        return redirect()
            ->route($request->user()->role->homeRoute())
            ->with('status', __('otp.verified'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $this->otp->issue($request->user());

        return back()->with('status', __('otp.sent'));
    }
}
