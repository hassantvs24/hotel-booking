<x-admin.layout title="Users">
    <x-admin.card>
        <x-admin.card.card-header title="Users" class="d-flex align-content-center">
            <x-admin.page-action>
                <a href="{{ route('admin.acl.users.create') }}" class="btn btn-xs btn-outline-primary m-0">
                    <i class="fas fa-plus me-2"></i>
                    Create
                </a>
            </x-admin.page-action>
        </x-admin.card.card-header>

        <x-admin.card.card-body class="px-0 pt-0 pb-2">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Date" />
                    <x-admin.table-head value="Name" />
                    <x-admin.table-head value="Email" />
                    <x-admin.table-head value="Phone" />
                    <x-admin.table-head value="User Type" />
                    <x-admin.table-head value="Actions" class="text-right"/>
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($users as $user)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="format_date($user->created_at)" />
                            <x-admin.table-cell :value="$user->name" />
                            <x-admin.table-cell :value="$user->email" />
                            <x-admin.table-cell :value="$user->phone" />
                            <x-admin.table-cell>
                                <x-admin.badge type="primary" :text="$user->user_type" />
                            </x-admin.table-cell>
                            <x-admin.table-cell class="text-right">
                                <a href="{{ route('admin.acl.users.edit', $user->id) }}" class="btn btn-icon btn-xs btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-icon btn-xs btn-outline-danger">
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
        <x-admin.card.card-footer>
            {{ $users->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
