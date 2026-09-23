{{--
    Admin brand mark. The white logo sits on the dark sidebar; the colour logo
    is used on light surfaces (login card, phone top bar). The theme CSS picks
    the right one for each context.
--}}
<span class="th-brand">
    <img src="{{ asset('images/brand/logo-full-color.png') }}" alt="TawreedHub" class="th-brand-logo th-brand-logo-color" />
    <img src="{{ asset('images/brand/logo-white.png') }}" alt="TawreedHub" class="th-brand-logo th-brand-logo-white" />
    <span class="th-brand-pill">{{ __('admin.admin_badge') }}</span>
</span>
