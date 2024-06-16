<x-admin.layout title="Permissions">
    <x-admin.card>
        <x-admin.card.card-header title="Permissions" class="d-flex align-content-center">
            <x-admin.page-action>
            </x-admin.page-action>
        </x-admin.card.card-header>

        <x-admin.card.card-body class="px-0 pt-0 pb-2">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Name" />
                    <x-admin.table-head value="Slug" />
                    <x-admin.table-head value="Subject" />
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($permissions as $permission)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="$permission->name" />
                            <x-admin.table-cell :value="$permission->slug" />
                            <x-admin.table-cell :value="$permission->subject" />
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
            {{ $permissions->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
