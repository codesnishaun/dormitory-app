@php
    $suggestions = [
        'Summarize current room occupancy',
        'Which rooms are currently available?',
        'Show currently published announcements',
        'Find approved guidance about dormitory policies',
    ];

    $helpItems = [
        'Check room occupancy and availability from current dormitory records.',
        'Review published announcements and approved dormitory guidance.',
        'Use the admin workspaces for billing, approvals, and any record changes.',
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
        welcome-message="Ask about live room information, published announcements, or approved dormitory guidance. Choose a prompt to get started."
        help-title="Admin assistant"
        :suggestions="$suggestions"
        :help-items="$helpItems"
        :action-links="$actionLinks"
    />
</x-admin-layout>
