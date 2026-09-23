<x-dynamic-component :component="'layouts.'.$shell" current="chats" :title="__('common.nav_messages')">
    <x-chat.frame :conversations="$conversations" :active="$conversation">
        <livewire:chat.thread :conversation="$conversation" />
    </x-chat.frame>
</x-dynamic-component>
