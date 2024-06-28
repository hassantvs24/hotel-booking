<x-admin.layout title="Properties">
    <x-admin.card>
        <x-admin.card.card-header title="Properties" class="d-flex align-content-center">
            @hasPermission($permissions['create'])
            <x-admin.page-action>
                <a href="{{ route('admin.properties.create') }}" class="m-0 btn btn-xs btn-outline-primary">
                    <i class="fas fa-plus me-2"></i>
                    Add Property
                </a>
            </x-admin.page-action>
            @endHasPermission
        </x-admin.card.card-header>

        <x-admin.card.card-body class="px-0 pt-0 pb-2 table-responsive">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Date"/>
                    <x-admin.table-head value="Name"/>
                    <x-admin.table-head value="Latitude"/>
                    <x-admin.table-head value="Longitude"/>
                    <x-admin.table-head value="Photo"/>
                    <x-admin.table-head value="Address"/>
                    <x-admin.table-head value="Zip Code"/>
                    <x-admin.table-head value="Total Room"/>
                    <x-admin.table-head value="Currency"/>
                    <x-admin.table-head value="Rating"/>
                    <x-admin.table-head value="Google Review"/>
                    <x-admin.table-head value="Seo Title"/>
                    <x-admin.table-head value="Seo Meta"/>
                    <x-admin.table-head value="Property Standered"/>
                    <x-admin.table-head value="Status"/>
                    <x-admin.table-head value="Property Category"/>
                    <x-admin.table-head value="Place"/>
                    <x-admin.table-head value="User"/>
                    @if( hasPermission($permissions['update']) || hasPermission($permissions['delete']) )
                        <x-admin.table-head class="text-right" value="Actions"/>
                    @endif
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($properties as $property)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="format_date($property->created_at)"/>
                            <x-admin.table-cell :value="$property->name"/>
                            <x-admin.table-cell :value="$property->lat"/>
                            <x-admin.table-cell :value="$property->long"/>
                            <x-admin.table-cell :value="$property->photo"/>
                            <x-admin.table-cell :value="$property->address"/>
                            <x-admin.table-cell :value="$property->zip_code"/>
                            <x-admin.table-cell :value="$property->total_room"/>
                            <x-admin.table-cell :value="$property->currency"/>
                            <x-admin.table-cell :value="$property->rating"/>
                            <x-admin.table-cell :value="$property->google_review"/>
                            <x-admin.table-cell :value="$property->seo_title"/>
                            <x-admin.table-cell :value="$property->seo_meta"/>
                            <x-admin.table-cell :value="$property->property_class"/>
                            <x-admin.table-cell :value="$property->status"/>
                            <x-admin.table-cell :value="$property->propertyCategory?->name"/>
                            <x-admin.table-cell :value="$property->place?->name"/>
                            <x-admin.table-cell :value="$property->user?->name"/>
                            @if( hasPermission($permissions['update']) || hasPermission($permissions['delete']) )
                                <x-admin.table-cell class="text-right">
                                    @hasPermission($permissions['update'])
                                    <a href="{{ route('admin.properties.edit', $property->id) }}"
                                       class="btn btn-icon btn-xs btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endHasPermission
                                    @hasPermission($permissions['delete'])
                                    <x-admin.form
                                            action="{{ route('admin.properties.destroy', $property->id) }}"
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
            {{ $properties->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
