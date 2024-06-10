@php
    $headers = ['name', 'email', 'role', 'status', 'date', 'actions'];
    $rows = [
        [
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'role' => 'Admin',
            'status' => 'Active',
            'created_at' => '2021-10-10',
        ],
        [
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'role' => 'Admin',
            'status' => 'Active',
            'created_at' => '2021-10-10',
        ],
        // Add more rows as needed...
    ];
@endphp

<x-admin.layout title="Users">
    <x-admin.card>
        <x-admin.card.card-header title="Users" class="d-flex align-content-center">
            <x-admin.page-action>
                <a href="{{ route('admin.acl.users.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Create
                </a>
            </x-admin.page-action>
        </x-admin.card.card-header>

        <x-admin.card.card-body class="px-0 pt-0 pb-2">
            <x-admin.data-table
                :headers="$headers"
                :rows="$rows"
            />
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>