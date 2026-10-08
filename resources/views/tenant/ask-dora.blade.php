@php
    $suggestions = [
        'Explain my current bill',
        'Help me write a maintenance request',
        'What are the visitor guidelines?',
        'How do I report a room issue?',
    ];

    $helpItems = [
        'Understand your bill and common charges.',
        'Find the right place to ask for repairs.',
        'Get help with dormitory guidelines and announcements.',
    ];
@endphp

<x-tenant-layout body-class="min-h-screen font-sans text-ink">
    <x-slot:title>Ask DORA - DORA</x-slot:title>

    <x-header
        title="Ask DORA"
        description="A friendly place to get help with everyday dormitory questions."
    />

    <x-chat-assistant
        audience="Resident"
        welcome-message="Ask about bills, maintenance, announcements, or dormitory guidelines. Choose a prompt to get started."
        help-title="How DORA can help"
        :suggestions="$suggestions"
        :help-items="$helpItems"
    />
</x-tenant-layout>
