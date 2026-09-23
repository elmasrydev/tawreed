@props(['conversations', 'active' => null])

{{--
    Two-pane messages frame. Desktop: 320px thread list beside the
    conversation, filling the viewport under the top bar. Phones: only one
    pane at a time (the list on the index, the thread on a conversation).
--}}
<div @class([
    'lg:sticky lg:top-[60px] lg:grid lg:h-[calc(100dvh-60px)] lg:grid-cols-[320px_minmax(0,1fr)]',
    'max-lg:sticky max-lg:top-14 max-lg:-mb-20 max-lg:flex max-lg:h-[calc(100dvh-7rem-env(safe-area-inset-bottom))] max-lg:flex-col' => $active,
])>
    <aside x-data="{
               query: '',
               matches(el) { return ! this.query.trim() || el.dataset.search.includes(this.query.trim().toLowerCase()) },
               none() { return ! [...this.$refs.threads.querySelectorAll('[data-search]')].some((el) => this.matches(el)) },
           }"
           @class([
        'min-h-0 flex-col border-gray-200 bg-white lg:flex lg:border-e',
        'hidden' => $active,
        'flex min-h-[calc(100dvh-8.5rem)]' => ! $active,
    ])>
        <x-chat.thread-list :conversations="$conversations" :active="$active" />
    </aside>

    <section @class([
        'min-h-0 min-w-0 flex-1 flex-col bg-gray-50',
        'flex' => $active,
        'hidden lg:flex' => ! $active,
    ])>
        {{ $slot }}
    </section>
</div>
