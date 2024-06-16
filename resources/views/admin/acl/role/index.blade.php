<x-admin.layout title="Users">
    <x-admin.card>
        <x-admin.card.card-header title="Users" class="d-flex align-content-center">
            <x-admin.page-action>
                <a href="{{ route('admin.acl.roles.create') }}" class="btn btn-xs btn-outline-primary m-0">
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
                    <x-admin.table-head value="Description" />
                    <x-admin.table-head value="Actions" class="text-right"/>
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($roles as $role)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="format_date($role->created_at)" />
                            <x-admin.table-cell :value="$role->name" />
                            <x-admin.table-cell :value="$role->description" />
                            <x-admin.table-cell class="text-right">
                                <div class="d-flex">
                                <a href="{{ route('admin.acl.roles.edit', $role->id) }}" class="btn btn-icon btn-xs btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.acl.roles.destroy', $role->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-icon btn-xs btn-outline-danger" type="submit">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
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
            {{ $roles->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
