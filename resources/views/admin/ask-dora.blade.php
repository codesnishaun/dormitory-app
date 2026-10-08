@php
    $suggestions = [
        'Summarize open maintenance requests',
        'Draft an announcement for residents',
        'What should I review in this month’s billing?',
        'Help me write a resident reminder',
    ];

    $helpItems = [
        'Prepare resident-facing announcements and reminders.',
        'Find relevant admin sections for common tasks.',
        'Organize operational questions for future assistant workflows.',
    ];

    $actionLinks = [
        ['label' => 'Maintenance requests', 'url' => route('admin.maintenance.index')],
        ['label' => 'Billing overview', 'url' => route('admin.billing.index')],
        ['label' => 'Announcements', 'url' => route('admin.announcement.index')],
    ];
@endphp

<x-admin-layout>
    <x-slot:title>Ask DORA - Admin</x-slot:title>

    <x-header
        title="DORA for admins"
        description="A workspace for operational questions, resident communications, and day-to-day tasks."
    />

    <x-chat-assistant
        audience="Admin"
        welcome-message="Ask about dorm operations or start with a suggested task. Admin workspace links are available alongside the chat."
        help-title="Admin assistant"
        :suggestions="$suggestions"
        :help-items="$helpItems"
        :action-links="$actionLinks"
    />
</x-admin-layout>
