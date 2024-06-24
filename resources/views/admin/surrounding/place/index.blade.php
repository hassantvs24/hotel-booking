<x-admin.layout title="Surrounding Places">
    <x-admin.card>
        <x-admin.card.card-header title="Surrounding Places" class="d-flex align-content-center">
            @hasPermission($permissions['create'])
            <x-admin.page-action>
                <a href="{{ route('admin.surroundings.surrounding-places.create') }}" class="btn btn-xs btn-outline-primary m-0">
                    <i class="fas fa-plus me-2"></i>
                    Add Surrounding Place
                </a>
            </x-admin.page-action>
            @endHasPermission
        </x-admin.card.card-header>
        <x-admin.card.card-body class="px-0 pt-0 pb-2">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Name"/>
                    <x-admin.table-head value="Latitude"/>
                    <x-admin.table-head value="Longitude"/>
                    <x-admin.table-head value="Note"/>
                    <x-admin.table-head value="photo"/>
                    <x-admin.table-head value="Place"/>
                    <x-admin.table-head value="Surrounding"/>
                    @if( hasPermission($permissions['update']) || hasPermission($permissions['delete']) )
                        <x-admin.table-head class="text-right" value="Actions"/>
                    @endif
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($surroundingPlaces as $surroundingPlace)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="$surroundingPlace->name"/>
                            <x-admin.table-cell :value="$surroundingPlace->lat"/>
                            <x-admin.table-cell :value="$surroundingPlace->long"/>
                            <x-admin.table-cell class="word-wrap" :value="$surroundingPlace->notes"/>
                            <x-admin.table-cell :value="$surroundingPlace->photo"/>
                            <x-admin.table-cell :value="$surroundingPlace->place?->name"/>
                            <x-admin.table-cell :value="$surroundingPlace->surrounding?->name"/>
                            @if( hasPermission($permissions['update']) || hasPermission($permissions['delete']) )
                                <x-admin.table-cell class="text-right">
                                    @hasPermission($permissions['update'])
                                    <a href="{{ route('admin.surroundings.surrounding-places.edit', $surroundingPlace->id) }}"
                                       class="btn btn-icon btn-xs btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endHasPermission
                                    @hasPermission($permissions['delete'])
                                    <x-admin.form
                                            action="{{ route('admin.surroundings.surrounding-places.destroy', $surroundingPlace->id) }}"
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
            {{ $surroundingPlaces->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
