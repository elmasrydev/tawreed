<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /**
     * With codes switched off, an unverified account is verified on arrival
     * and sent on to its home page.
     */
    public function show(Request $request): View|RedirectResponse
    {
        if (! $this->otp->isEnabled()) {
            $this->otp->startVerification($request->user());
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->role->homeRoute());
        }

        return view('auth.verify-email');
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
        if (! $this->otp->isEnabled()) {
            return redirect()->route('email.verify');
        }

        $this->otp->issue($request->user());

        return back()->with('status', __('otp.sent'));
    }
}
