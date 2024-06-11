@php
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
        [
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'role' => 'Admin',
            'status' => 'Active',
            'created_at' => '2021-10-10',
        ]
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
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Name" />
                    <x-admin.table-head value="Email" />
                    <x-admin.table-head value="Role" />
                    <x-admin.table-head value="Status" />
                    <x-admin.table-head value="Date" />
                    <x-admin.table-head value="Actions" class="text-right"/>
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($rows as $row)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="$row['name']" />
                            <x-admin.table-cell :value="$row['email']" />
                            <x-admin.table-cell :value="$row['role']" />
                            <x-admin.table-cell :value="$row['status']" />
                            <x-admin.table-cell :value="$row['created_at']" />
                            <x-admin.table-cell class="text-right">
                                <a href="{{ route('admin.acl.users.edit', 1) }}" class="btn btn-icon btn-sm btn-primary me-2">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-icon btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </x-admin.table-cell>
                        </x-admin.table-row>
                    @empty
                        <x-admin.table-row>
                            <x-admin.table-cell colspan="6">No records found.</x-admin.table-cell>
                        </x-admin.table-row>
                    @endforelse
                </x-admin.table-body>
            </x-admin.table>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>