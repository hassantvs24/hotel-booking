<x-admin.layout title="Places">
    <x-admin.card>
        <x-admin.card.card-header title="Places" class="d-flex align-content-center">
            @hasPermission($permissions['create'])
            <x-admin.page-action>
                <a href="{{ route('admin.places.create') }}" class="m-0 btn btn-xs btn-outline-primary">
                    <i class="fas fa-plus me-2"></i>
                    Add Place
                </a>
            </x-admin.page-action>
            @endHasPermission
        </x-admin.card.card-header>

        <x-admin.card.card-body class="px-0 pt-0 pb-2 table-responsive">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Icon"/>
                    <x-admin.table-head value="Image"/>
                    <x-admin.table-head value="Name"/>
                    <x-admin.table-head value="Co-Ordinates"/>
                    <x-admin.table-head value="Zip Code"/>
                    <x-admin.table-head value="Nearest"/>
                    <x-admin.table-head value="City"/>
                    @if( hasPermission($permissions['update']) || hasPermission($permissions['delete']) )
                        <x-admin.table-head class="text-right" value="Actions"/>
                    @endif
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($places as $place)
                        <x-admin.table-row>
                            <x-admin.table-cell>
                                <img
                                    width="30"
                                    height="30"
                                    src="{{ $place->icon_url }}"
                                    alt="{{ $place->name }}"
                                >
                            </x-admin.table-cell>
                            <x-admin.table-cell>
                                <img
                                    width="30"
                                    height="30"
                                    src="{{ $place->primary_image_url }}"
                                    alt="{{ $place->name }}"
                                >
                            </x-admin.table-cell>
                            <x-admin.table-cell :value="$place->name"/>
                            <x-admin.table-cell>
                                <ul>
                                    <li>Lat: {{ $place->lat }}</li>
                                    <li>Lon: {{ $place->long }}</li>
                                </ul>
                            </x-admin.table-cell>
                            <x-admin.table-cell :value="$place->zip_code"/>
                            <x-admin.table-cell>
                                <ul>
                                    <li>Police Station: {{ $place->nearest_police }}</li>
                                    <li>Fire Station: {{ $place->nearest_fire }}</li>
                                    <li>Hospital: {{ $place->nearest_hospital }}</li>
                                </ul>
                            </x-admin.table-cell>
                            <x-admin.table-cell :value="$place->city?->name"/>
                            @if( hasPermission($permissions['update']) || hasPermission($permissions['delete']) )
                                <x-admin.table-cell class="text-right">
                                    @hasPermission($permissions['update'])
                                    <a href="{{ route('admin.places.edit', $place->id) }}"
                                       class="btn btn-icon btn-xs btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endHasPermission
                                    @hasPermission($permissions['delete'])
                                    <x-admin.form
                                            action="{{ route('admin.places.destroy', $place->id) }}"
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
            {{ $places->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
