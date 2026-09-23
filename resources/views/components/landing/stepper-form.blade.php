@props(['action', 'total', 'initial' => 1, 'multipart' => false])

{{--
    One real form split into client-side steps. "Continue" only checks the
    visible step's required fields and moves on; the whole form posts once at
    the end. Children mark each step with data-step="n", and checkbox groups
    that need at least one choice with data-required-group.
--}}
<form method="POST" action="{{ $action }}" novalidate
      @if ($multipart) enctype="multipart/form-data" @endif
      x-data="{
          step: {{ (int) $initial }},
          total: {{ (int) $total }},
          fields(step) {
              return this.$root.querySelectorAll(`[data-step='${step}'] input, [data-step='${step}'] select, [data-step='${step}'] textarea`);
          },
          isStepValid(step, report) {
              for (const field of this.fields(step)) {
                  const wrapper = field.closest('[data-field]');
                  if (field.type !== 'hidden' && ! field.checkValidity()) {
                      wrapper?.setAttribute('data-invalid', '');
                      if (report) {
                          this.step = step;
                          this.$nextTick(() => wrapper ? wrapper.scrollIntoView({ block: 'center', behavior: 'smooth' }) : field.reportValidity());
                      }
                      return false;
                  }
                  wrapper?.removeAttribute('data-invalid');
              }
              for (const group of this.$root.querySelectorAll(`[data-step='${step}'] [data-required-group]`)) {
                  const checked = group.querySelector('input:checked') !== null;
                  group.toggleAttribute('data-invalid', ! checked);
                  if (! checked) {
                      if (report) { this.step = step; this.$nextTick(() => group.scrollIntoView({ block: 'center', behavior: 'smooth' })); }
                      return false;
                  }
              }
              return true;
          },
          next() {
              if (this.isStepValid(this.step, true)) { this.step = Math.min(this.step + 1, this.total); this.top(); }
          },
          back() { this.step = Math.max(this.step - 1, 1); this.top(); },
          top() { this.$nextTick(() => this.$root.scrollIntoView({ block: 'start', behavior: 'smooth' })); },
          submit(event) {
              for (let step = 1; step <= this.total; step++) {
                  if (! this.isStepValid(step, true)) { event.preventDefault(); return; }
              }
          },
      }"
      @submit="submit($event)"
      {{ $attributes->class('flex scroll-mt-6 flex-col gap-6') }}>
    @csrf
    {{ $slot }}
</form>
