<x-admin.layout title="Bed Types">
    <x-admin.card>
        <x-admin.card.card-header title="Bed Types" class="d-flex align-content-center">
            @hasPermission($permissions['create'])
                <x-admin.page-action>
                    <a href="{{ route('admin.rooms.bed-types.create') }}" class="m-0 btn btn-xs btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>
                        Add Bed Type
                    </a>
                </x-admin.page-action>
            @endHasPermission
        </x-admin.card.card-header>

        <x-admin.card.card-body class="px-0 pt-0 pb-2">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Date"/>
                    <x-admin.table-head value="Name"/>
                    <x-admin.table-head value="Capacity"/>
                    <x-admin.table-head value="Total Bed"/>
                    <x-admin.table-head value="Bed Size"/>
                    @if( hasPermission($permissions['update']) || hasPermission($permissions['delete']) )
                        <x-admin.table-head value="Actions"/>
                    @endif
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($bedTypes as $bedType)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="format_date($bedType->created_at)"/>
                            <x-admin.table-cell :value="$bedType->name"/>
                            <x-admin.table-cell :value="$bedType->capacity" />
                            <x-admin.table-cell :value="$bedType->total_bed" />
                            <x-admin.table-cell :value="$bedType->bed_size" />
                            @if( hasPermission($permissions['update']) || hasPermission($permissions['delete']) )
                                <x-admin.table-cell>
                                    @hasPermission($permissions['update'])
                                        <a href="{{ route('admin.rooms.bed-types.edit', $bedType->id) }}"
                                           class="btn btn-icon btn-xs btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endHasPermission
                                    @hasPermission($permissions['delete'])
                                        <x-admin.form
                                                action="{{ route('admin.rooms.bed-types.destroy', $bedType->id) }}"
                                                method="delete"
                                                class="d-inline"
                                        >
                                            <button onclick="return confirm('Want to delete?')" type="submit"
                                                    class="btn btn-icon btn-xs btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </x-admin.form>
                                    @endHasPermission
                                </x-admin.table-cell>
                            @endif
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
            {{ $bedTypes->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
