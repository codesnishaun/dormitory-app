@php
    $suggestions = [
        'What is my current room assignment?',
        'Explain my latest electricity bill',
        'What is my outstanding balance?',
        'Check my maintenance or absence request status',
    ];

    $helpItems = [
        'Understand your bill and common charges.',
        'Check the status of your own maintenance and absence requests.',
        'Use the tenant forms to submit a new maintenance or absence request.',
        'Get help with approved dormitory guidance and published announcements.',
    ];

    $actionLinks = [
        ['label' => 'View billing and payment history', 'url' => route('tenant.room-bill')],
        ['label' => 'Check or submit an absence request', 'url' => route('tenant.absence-request')],
        ['label' => 'Check or submit a maintenance request', 'url' => route('tenant.maintenance')],
        ['label' => 'Read announcements', 'url' => route('tenant.announcements')],
    ];
@endphp

<x-tenant-layout body-class="h-dvh overflow-hidden font-sans text-ink">
    <x-slot:title>Ask DORA - DORA</x-slot:title>

    <x-header
        title="Ask DORA"
        description="A friendly place to get help with everyday dormitory questions."
    />

    <x-chat-assistant
        audience="Resident"
        welcome-message="Ask about your room, bills, request statuses, announcements, or dormitory guidance. Choose a prompt to get started."
        help-title="How DORA can help"
        :suggestions="$suggestions"
        :help-items="$helpItems"
        :action-links="$actionLinks"
    />
</x-tenant-layout>
